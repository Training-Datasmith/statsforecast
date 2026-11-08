#!/usr/bin/env bash
set -eu

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$REPO_ROOT"

USE_SUDO_DOCKER="${USE_SUDO_DOCKER:-0}"
DOCKER_BIN="${DOCKER:-docker}"
if [ "$USE_SUDO_DOCKER" = "1" ]; then
  DOCKER_BIN="sudo ${DOCKER_BIN}"
fi

EXPECTED_FILES=(
  tests/phpunit/AttributeDistributionTest.php
  tests/phpunit/CategoryDistributionTest.php
  tests/phpunit/ConversionFunnelTest.php
  tests/phpunit/CurrencyAndZoneTest.php
  tests/phpunit/DashboardOrdersTest.php
  tests/phpunit/GranularityTest.php
  tests/phpunit/GuardAndFrontControllersTest.php
  tests/phpunit/LanguageDistributionTest.php
  tests/phpunit/ModuleContractTest.php
  tests/phpunit/PaymentDistributionTest.php
)

mapfile -t ACTUAL_FILES < <(find tests/phpunit -maxdepth 1 -name '*Test.php' | LC_ALL=C sort)
if [ "${#ACTUAL_FILES[@]}" -ne "${#EXPECTED_FILES[@]}" ]; then
  echo "test file count drift" >&2
  exit 1
fi
for i in "${!EXPECTED_FILES[@]}"; do
  if [ "${EXPECTED_FILES[$i]}" != "${ACTUAL_FILES[$i]}" ]; then
    echo "test file list drift at index $i: expected ${EXPECTED_FILES[$i]}, got ${ACTUAL_FILES[$i]}" >&2
    exit 1
  fi
done

WORKDIR=""
OUT_DIR=""
export GNUPGHOME
GNUPGHOME="$(mktemp -d)"
chmod 700 "$GNUPGHOME"
cleanup() {
  rm -rf "$GNUPGHOME" "$WORKDIR" "$OUT_DIR" 2>/dev/null || true
  $DOCKER_BIN rm -f statsforecast-db >/dev/null 2>&1 || true
}
trap cleanup EXIT

gpg --batch --keyserver hkps://keyserver.ubuntu.com --recv-keys D8406D0D82947747293778314AA394086372C20A
FP="$(gpg --batch --with-colons --fingerprint D8406D0D82947747293778314AA394086372C20A | awk -F: '/^fpr:/ {print $10; exit}')"
if [ "$FP" != "D8406D0D82947747293778314AA394086372C20A" ]; then
  echo "unexpected GPG fingerprint: $FP" >&2
  exit 1
fi

WORKDIR="$(mktemp -d)"
chmod 700 "$WORKDIR"
curl -fsSL -o "$WORKDIR/phpunit-4.8.36.phar" https://phar.phpunit.de/phpunit-4.8.36.phar
curl -fsSL -o "$WORKDIR/phpunit-4.8.36.phar.asc" https://phar.phpunit.de/phpunit-4.8.36.phar.asc
echo "ab8bc3017a64cf75a2112f5e089a1d5b0c4cc2116556d60c1af5e3f584e85c5e  $WORKDIR/phpunit-4.8.36.phar" | sha256sum -c -
gpg --batch --status-fd 1 --verify "$WORKDIR/phpunit-4.8.36.phar.asc" "$WORKDIR/phpunit-4.8.36.phar" | tee "$WORKDIR/gpg.status"
grep -q 'VALIDSIG D8406D0D82947747293778314AA394086372C20A' "$WORKDIR/gpg.status"

$DOCKER_BIN pull mariadb@sha256:6a12617127de7ad42895232407abf403ae79f465bb818a8ac7ffbb97b8b18937
$DOCKER_BIN pull php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143
$DOCKER_BIN build -t statsforecast-php56 -f tests/docker/php56/Dockerfile tests/docker/php56

if ! $DOCKER_BIN network inspect statsforecast-net >/dev/null 2>&1; then
  $DOCKER_BIN network create statsforecast-net
fi
$DOCKER_BIN rm -f statsforecast-db >/dev/null 2>&1 || true
$DOCKER_BIN run -d --name statsforecast-db --network statsforecast-net --network-alias db \
  -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=statsforecast \
  -e MYSQL_USER=sf -e MYSQL_PASSWORD=sf \
  mariadb@sha256:6a12617127de7ad42895232407abf403ae79f465bb818a8ac7ffbb97b8b18937

READY=0
for _ in $(seq 1 90); do
  if $DOCKER_BIN exec statsforecast-db mysqladmin ping -uroot -proot --silent >/dev/null 2>&1 \
    && $DOCKER_BIN run --rm --network container:statsforecast-db statsforecast-php56 \
      php -r '$m = @new mysqli("127.0.0.1", "sf", "sf", "statsforecast"); if ($m->connect_error) { exit(1); } exit(0);' \
      >/dev/null 2>&1; then
    READY=1
    break
  fi
  sleep 1
done
if [ "$READY" -ne 1 ]; then
  echo "database not ready" >&2
  exit 1
fi

OUT_DIR="$(mktemp -d)"
chmod 700 "$OUT_DIR"

phpunit_run() {
  local config="$1"
  local junit="$2"
  local log="$3"
  $DOCKER_BIN run --rm --network container:statsforecast-db \
    -e SF_DB_HOST=127.0.0.1 -e SF_DB_USER=sf -e SF_DB_PASSWORD=sf -e SF_DB_NAME=statsforecast -e TZ=UTC \
    -v "$REPO_ROOT":/work:ro -v "$WORKDIR":/opt/phpunit:ro -v "$OUT_DIR":/tmp/suite \
    -w /work statsforecast-php56 \
    sh -c "php -r 'echo PHP_VERSION, \"\\n\";' | grep -qx '5.6.40' && php -d error_reporting=-1 /opt/phpunit/phpunit-4.8.36.phar --configuration '$config' --log-junit '$junit'" \
    | tee "$log"
}

phpunit_run /work/phpunit.xml.dist /tmp/suite/junit-ordered.xml "$OUT_DIR/ordered.log"

assert_run() {
  local log="$1"
  local junit="$2"
  local out
  out="$(cat "$log")"
  if echo "$out" | grep -qE '\b(Notice|Warning|Deprecated|Strict Standards|Parse error|Fatal error|Recoverable fatal error)\b'; then
    echo "PHPUnit output contains PHP diagnostic" >&2
    exit 1
  fi
  if echo "$out" | grep -qE 'Skipped:|Incomplete:|There was'; then
    echo "PHPUnit output contains skipped/incomplete/warning summary" >&2
    exit 1
  fi
  last="$(echo "$out" | awk 'NF {line=$0} END {print line}')"
  if ! echo "$last" | grep -qE '^OK \(52 tests, [0-9]+ assertions\)$'; then
    echo "unexpected PHPUnit summary line: $last" >&2
    exit 1
  fi
  suite_line="$(grep -m1 'testsuite name="module-tests"' "$junit" || true)"
  if [ -z "$suite_line" ] \
    || ! echo "$suite_line" | grep -q 'tests="52"' \
    || ! echo "$suite_line" | grep -q 'failures="0"' \
    || ! echo "$suite_line" | grep -q 'errors="0"'; then
    echo "unexpected junit summary in $junit" >&2
    exit 1
  fi
  if echo "$suite_line" | grep -q 'skipped=' && ! echo "$suite_line" | grep -q 'skipped="0"'; then
    echo "unexpected skipped count in $junit" >&2
    exit 1
  fi
}

assert_run "$OUT_DIR/ordered.log" "$OUT_DIR/junit-ordered.xml"

$DOCKER_BIN run --rm --network container:statsforecast-db \
  -e SUITE_OUT=/tmp/suite/phpunit-shuffle.xml \
  -v "$REPO_ROOT":/work:ro -v "$OUT_DIR":/tmp/suite \
  statsforecast-php56 \
  php /work/tests/phpunit/generate-shuffle-xml.php

phpunit_run /tmp/suite/phpunit-shuffle.xml /tmp/suite/junit-shuffle.xml "$OUT_DIR/shuffle.log"
assert_run "$OUT_DIR/shuffle.log" "$OUT_DIR/junit-shuffle.xml"

echo "suite OK (ordered + shuffled)"
