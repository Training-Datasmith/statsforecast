<?php

error_reporting(-1);
ini_set('display_errors', '1');
$target = getenv('GUARD_TARGET');
if (!$target) {
    fwrite(STDERR, "missing GUARD_TARGET\n");
    exit(2);
}
include $target;
echo "SENTINEL\n";
