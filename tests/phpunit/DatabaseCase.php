<?php

class DatabaseCase extends PHPUnit_Framework_TestCase
{
    protected static $tables = array(
        'order_detail',
        'order_payment',
        'orders',
        'connections',
        'customer',
        'guest',
        'cart_product',
        'cart',
        'address',
        'country',
        'attribute_lang',
        'attribute_group_lang',
        'attribute',
        'product_attribute_combination',
        'category_lang',
        'product_shop',
        'product',
        'lang_shop',
        'lang',
        'currency',
        'zone',
    );

    protected function setUp()
    {
        Context::resetForTests();
        Shop::resetForTests();
        Module::resetForTests();
        Link::resetForTests();
        Db::resetInstance();

        $_POST = array();
        $_GET = array();

        $ctx = Context::getContext();
        $ctx->cookie = new stdClass();
        $ctx->cookie->stats_id_zone = 0;
        $ctx->employee->stats_date_from = '2020-01-01';
        $ctx->employee->stats_date_to = '2020-01-07';
        $ctx->clearLocaleLog();

        Fixture::resetCounters();
        $this->truncateAll();
    }

    protected function truncateAll()
    {
        $db = Db::getInstance()->getLink();
        foreach (self::$tables as $table) {
            $db->query('TRUNCATE TABLE ' . _DB_PREFIX_ . $table);
        }
    }

    protected function runHook()
    {
        $module = new statsforecast();

        return $module->hookDisplayAdminStatsModules();
    }

    protected function assertAmountNear($expected, $amount, $msg = '')
    {
        $this->assertTrue(is_numeric($amount), $msg . ' amount must be numeric');
        $this->assertEquals((float) $expected, (float) $amount, $msg, 0.001);
    }

    protected function assertLoggedAmountNear($expected)
    {
        $log = Context::getContext()->getLocaleLog();
        $this->assertNotEmpty($log, 'expected formatPrice log entry');
        $last = $log[count($log) - 1];
        $this->assertAmountNear($expected, $last[0], 'locale log amount');
    }

    protected function sectionAfter($html, $needle)
    {
        $parts = preg_split('/<h4[^>]*>/', $html);
        foreach ($parts as $part) {
            if (strpos($part, $needle) === 0 || strpos($part, $needle) !== false) {
                return $part;
            }
        }

        return '';
    }

    protected function runChildPhp($scriptPath, $env = array())
    {
        $php = PHP_BINARY;
        $cmd = escapeshellarg($php)
            . ' -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1'
            . ' -d html_errors=0 -d log_errors=0 '
            . escapeshellarg($scriptPath);
        $descriptor = array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w'));
        $procEnv = null;
        if ($env !== array()) {
            $procEnv = array_merge($_ENV, $env);
        }
        $proc = proc_open($cmd, $descriptor, $pipes, dirname(dirname(__DIR__)), $procEnv);
        $this->assertTrue(is_resource($proc));
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);

        return array('stdout' => $stdout, 'stderr' => $stderr, 'exit' => $exit);
    }

    protected function assertCleanChild(array $result, $forbidStdoutSubstring = null)
    {
        $this->assertSame(0, $result['exit'], 'child exit code');
        $diag = '/\b(Notice|Warning|Deprecated|Strict Standards|Parse error|Fatal error|Recoverable fatal error)\b/';
        $this->assertNotRegExp($diag, $result['stdout'] . $result['stderr']);
        if ($forbidStdoutSubstring !== null) {
            $this->assertNotContains($forbidStdoutSubstring, $result['stdout']);
        }
    }
}
