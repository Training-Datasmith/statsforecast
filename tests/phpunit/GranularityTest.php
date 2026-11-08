<?php

class GranularityTest extends DatabaseCase
{
    public function testMonthlyBucketsAndRegistrationLikePrefix()
    {
        Fixture::seedCatalog();
        Context::getContext()->cookie->stats_granularity = 7;
        Fixture::setEmployeeRange('2020-01-01', '2020-02-29');
        Fixture::insertOrder(array('invoice_date' => '2020-01-15 12:00:00', 'date_add' => '2020-01-15 12:00:00', 'total_paid_tax_excl' => 10));
        Fixture::insertOrder(array('invoice_date' => '2020-02-02 12:00:00', 'date_add' => '2020-02-02 12:00:00', 'total_paid_tax_excl' => 30));
        Fixture::insertCustomer('2020-01-20 10:00:00');
        Fixture::insertCustomer('2020-02-01 10:00:00');
        Fixture::insertConnection('2020-01-15 12:00:00');
        Fixture::insertConnection('2020-02-02 12:00:00');
        $html = $this->runHook();
        $this->assertContains('2020-01', $html);
        $this->assertContains('2020-02', $html);
        $this->assertNotContains('2020-01-15', $html);
    }

    public function testYearlyBuckets()
    {
        Fixture::seedCatalog();
        Context::getContext()->cookie->stats_granularity = 4;
        Fixture::setEmployeeRange('2019-01-01', '2020-12-31');
        Fixture::insertOrder(array('invoice_date' => '2019-06-01 12:00:00', 'date_add' => '2019-06-01 12:00:00', 'total_paid_tax_excl' => 5));
        Fixture::insertOrder(array('invoice_date' => '2020-06-01 12:00:00', 'date_add' => '2020-06-01 12:00:00', 'total_paid_tax_excl' => 9));
        $html = $this->runHook();
        $this->assertContains('PRICE(EUR:5.00)', $html);
        $this->assertContains('PRICE(EUR:9.00)', $html);
    }

    public function testWeeklyBucketsStartOnMonday()
    {
        Fixture::seedCatalog();
        Context::getContext()->cookie->stats_granularity = 42;
        Fixture::setEmployeeRange('2020-01-06', '2020-01-12');
        Fixture::insertOrder(array('invoice_date' => '2020-01-07 12:00:00', 'date_add' => '2020-01-07 12:00:00', 'total_paid_tax_excl' => 10));
        Fixture::insertOrder(array('invoice_date' => '2020-01-08 12:00:00', 'date_add' => '2020-01-08 12:00:00', 'total_paid_tax_excl' => 30));
        Fixture::insertCustomer('2020-01-07 10:00:00');
        Fixture::insertCustomer('2020-01-05 10:00:00');
        Fixture::insertConnection('2020-01-06 10:00:00');
        $html = $this->runHook();
        $this->assertContains('2020-01-06', $html);
        $this->assertContains('PRICE(EUR:40.00)', $html);
    }

    public function testYearBoundaryOrderIsStillCounted()
    {
        Fixture::seedCatalog();
        Context::getContext()->cookie->stats_granularity = 42;
        Fixture::setEmployeeRange('2019-12-30', '2020-01-05');
        Fixture::insertOrder(array('invoice_date' => '2020-01-01 12:00:00', 'date_add' => '2020-01-01 12:00:00', 'total_paid_tax_excl' => 17));
        $this->runHook();
        $found = false;
        foreach (Context::getContext()->getLocaleLog() as $entry) {
            if (is_numeric($entry[0]) && abs((float) $entry[0] - 17.0) < 0.001) {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    public function testSubmitGranularitySwitchesToMonthly()
    {
        Fixture::seedCatalog();
        unset(Context::getContext()->cookie->stats_granularity);
        Fixture::setEmployeeRange('2020-01-01', '2020-02-29');
        Fixture::insertOrder(array('invoice_date' => '2020-01-15 12:00:00', 'total_paid_tax_excl' => 10));
        Fixture::insertOrder(array('invoice_date' => '2020-02-02 12:00:00', 'total_paid_tax_excl' => 30));
        $_POST['submitGranularity'] = 1;
        $_POST['stats_granularity'] = '7';
        $html = $this->runHook();
        $this->assertEquals(7, Context::getContext()->cookie->stats_granularity);
        $this->assertContains('2020-01', $html);
        $this->assertNotContains('2020-01-15', $html);
    }

    public function testDefaultGranularityWhenCookieMissingIsDaily()
    {
        Fixture::seedCatalog();
        unset(Context::getContext()->cookie->stats_granularity);
        Fixture::setEmployeeRange('2020-05-01', '2020-05-07');
        Fixture::insertOrder(array('invoice_date' => '2020-05-02 12:00:00', 'date_add' => '2020-05-02 12:00:00'));
        $html = $this->runHook();
        $this->assertContains('2020-05-02', $html);
    }

    public function testGranularityValueIsCastBeforeItReachesSql()
    {
        Fixture::seedCatalog();
        Fixture::setEmployeeRange('2020-01-01', '2020-02-29');
        Fixture::insertOrder(array('invoice_date' => '2020-02-02 12:00:00', 'total_paid_tax_excl' => 30));
        $_POST['submitGranularity'] = 1;
        $_POST['stats_granularity'] = '7 OR 1=1';
        $before = (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'orders');
        $this->runHook();
        $after = (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'orders');
        $this->assertSame($before, $after);
    }
}
