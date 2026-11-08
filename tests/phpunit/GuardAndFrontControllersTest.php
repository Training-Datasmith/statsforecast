<?php

class GuardAndFrontControllersTest extends PHPUnit_Framework_TestCase
{
    private $repoRoot;

    protected function setUp()
    {
        $this->repoRoot = dirname(dirname(__DIR__));
    }

    public function testModuleFileExitsWhenVersionConstantMissing()
    {
        $script = dirname(__FILE__) . '/scripts/child_guard_module.php';
        $result = $this->runChild($script);
        $this->assertCleanChild($result);
        $this->assertSame('', $result['stdout']);
    }

    public function testRootIndexExitsCleanly()
    {
        $this->guardIndex($this->repoRoot . '/index.php');
    }

    public function testNestedTestsIndexExitsCleanly()
    {
        $this->guardIndex($this->repoRoot . '/tests/index.php');
    }

    public function testTranslationsIndexExitsCleanly()
    {
        $this->guardIndex($this->repoRoot . '/translations/index.php');
    }

    public function testUpgradeIndexExitsCleanly()
    {
        $this->guardIndex($this->repoRoot . '/upgrade/index.php');
    }

    public function testPhpstanIndexExitsCleanly()
    {
        $this->guardIndex($this->repoRoot . '/tests/phpstan/index.php');
    }

    private function guardIndex($target)
    {
        $script = dirname(__FILE__) . '/scripts/child_guard_index.php';
        $result = $this->runChild($script, array('GUARD_TARGET' => $target));
        $this->assertCleanChild($result);
        $this->assertSame('', $result['stdout']);
    }

    private function runChild($script, $env = array())
    {
        $cmd = escapeshellarg(PHP_BINARY)
            . ' -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1'
            . ' -d html_errors=0 -d log_errors=0 '
            . escapeshellarg($script);
        $descriptor = array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w'));
        $procEnv = null;
        if ($env !== array()) {
            $procEnv = array_merge($_ENV, $env);
        }
        $proc = proc_open($cmd, $descriptor, $pipes, $this->repoRoot, $procEnv);
        $this->assertTrue(is_resource($proc));
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);

        return array('stdout' => $stdout, 'stderr' => $stderr, 'exit' => $exit);
    }

    private function assertCleanChild($result)
    {
        $this->assertSame(0, $result['exit']);
        $diag = '/\b(Notice|Warning|Deprecated|Strict Standards|Parse error|Fatal error|Recoverable fatal error)\b/';
        $this->assertNotRegExp($diag, $result['stdout'] . $result['stderr']);
    }
}
