<?php

$files = array(
    '/work/tests/phpunit/AttributeDistributionTest.php',
    '/work/tests/phpunit/CategoryDistributionTest.php',
    '/work/tests/phpunit/ConversionFunnelTest.php',
    '/work/tests/phpunit/CurrencyAndZoneTest.php',
    '/work/tests/phpunit/DashboardOrdersTest.php',
    '/work/tests/phpunit/GranularityTest.php',
    '/work/tests/phpunit/GuardAndFrontControllersTest.php',
    '/work/tests/phpunit/LanguageDistributionTest.php',
    '/work/tests/phpunit/ModuleContractTest.php',
    '/work/tests/phpunit/PaymentDistributionTest.php',
);

srand(20261108);
shuffle($files);

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<phpunit bootstrap="/work/tests/phpunit/bootstrap.php" colors="false" backupGlobals="false"';
$xml .= ' convertErrorsToExceptions="true" convertNoticesToExceptions="true" convertWarningsToExceptions="true">';
$xml .= '<testsuites><testsuite name="module-tests">';
foreach ($files as $file) {
    $xml .= '<file>' . $file . '</file>';
}
$xml .= '</testsuite></testsuites></phpunit>';

$out = getenv('SUITE_OUT');
if (!$out) {
    $out = '/tmp/suite/phpunit-shuffle.xml';
}
file_put_contents($out, $xml);
