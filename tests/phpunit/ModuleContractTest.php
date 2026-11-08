<?php

class ModuleContractTest extends DatabaseCase
{
    public function testConstructorPublishesModuleMetadata()
    {
        $m = new statsforecast();
        $this->assertSame('statsforecast', $m->name);
        $this->assertSame('administration', $m->tab);
        $this->assertSame('2.1.0', $m->version);
        $this->assertSame('PrestaShop', $m->author);
        $this->assertSame(0, $m->need_instance);
        $this->assertSame('Stats Dashboard', $m->displayName);
        $this->assertSame(
            'Enrich your stats, add a summary of all your current statistics on your back office.',
            $m->description
        );
        $this->assertSame('1.7.6.0', $m->ps_versions_compliancy['min']);
        $this->assertSame(_PS_VERSION_, $m->ps_versions_compliancy['max']);
    }

    public function testInstallRegistersDisplayAdminStatsModulesWhenParentSucceeds()
    {
        Module::$parentInstallResult = true;
        $m = new statsforecast();
        $this->assertTrue($m->install());
        $this->assertSame(array('displayAdminStatsModules'), $m->registeredHooks);
    }

    public function testInstallStopsWhenParentInstallFails()
    {
        Module::$parentInstallResult = false;
        $m = new statsforecast();
        $this->assertFalse($m->install());
        $this->assertSame(array(), $m->registeredHooks);
    }

    public function testGetContentRedirectsToThisModulesStatsPage()
    {
        $m = new statsforecast();
        try {
            $m->getContent();
            $this->fail('expected StatsforecastRedirect');
        } catch (StatsforecastRedirect $e) {
            $this->assertContains('controller=AdminStats', $e->getMessage());
            $this->assertContains('module=statsforecast', $e->getMessage());
        }
    }

    public function testUpgrade204MovesLegacyHookToDisplayHook()
    {
        $m = new statsforecast();
        $this->assertTrue(upgrade_module_2_0_4($m));
        $this->assertSame(array('AdminStatsModules'), $m->unregisteredHooks);
        $this->assertSame(array('displayAdminStatsModules'), $m->registeredHooks);
    }

    public function testSortOrdersHigherRevenueBeforeLower()
    {
        $this->assertSame(-1, statsforecast_sort(array('orderSum' => 20), array('orderSum' => 10)));
    }

    public function testSortOrdersLowerRevenueAfterHigher()
    {
        $this->assertSame(1, statsforecast_sort(array('orderSum' => 10), array('orderSum' => 20)));
    }

    public function testSortReturnsZeroForEqualRevenue()
    {
        $a = array('orderSum' => 10);
        $b = array('orderSum' => 10);
        $this->assertInternalType('integer', $a['orderSum']);
        $this->assertInternalType('integer', $b['orderSum']);
        $this->assertSame(0, statsforecast_sort($a, $b));
        $as = array('orderSum' => '10.00');
        $bs = array('orderSum' => '10.00');
        $this->assertInternalType('string', $as['orderSum']);
        $this->assertSame(0, statsforecast_sort($as, $bs));
    }
}
