<?php

error_reporting(-1);
ini_set('display_errors', '1');
define('_PS_CREATION_DATE_', '2020-04-02');
putenv('SF_DB_HOST=' . (getenv('SF_DB_HOST') ? getenv('SF_DB_HOST') : 'db'));
require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Fixture.php';
Fixture::setEmployeeRange('2020-04-01', '2020-04-03');
$ctx = Context::getContext();
$ctx->cookie = new stdClass();
$ctx->cookie->stats_id_zone = 0;
$module = new statsforecast();
echo $module->hookDisplayAdminStatsModules();
