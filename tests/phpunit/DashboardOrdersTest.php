<?php

class DashboardOrdersTest extends DatabaseCase
{
    public function testEmptyPastRangeRendersZeroDaysWithoutDiagnostics()
    {
        Fixture::setEmployeeRange('2020-04-01', '2020-04-03');
        $html = $this->runHook();
        $this->assertContains('2020-04-01', $html);
        $this->assertContains('2020-04-02', $html);
        $this->assertContains('2020-04-03', $html);
        $this->assertContains('>Total<', $html);
        $this->assertRegExp('/<td class="text-center">0<\/td>/', $html);
    }

    public function testDailyRowUsesTaxExcludedAmountDividedByConversionRate()
    {
        Fixture::seedCatalog();
        Fixture::setEmployeeRange('2020-01-06', '2020-01-06');
        Fixture::insertOrder(array(
            'invoice_date' => '2020-01-06 15:00:00',
            'date_add' => '2020-01-06 15:00:00',
            'total_paid_tax_excl' => 80,
            'total_paid_tax_incl' => 100,
            'conversion_rate' => 2,
            'lines' => array(array('product_quantity' => 2, 'unit_price_tax_excl' => 15, 'product_price' => 99)),
        ));
        $html = $this->runHook();
        $this->assertContains('2020-01-06', $html);
        $this->assertContains('PRICE(EUR:40.00)', $html);
        $log = Context::getContext()->getLocaleLog();
        $found = false;
        foreach ($log as $entry) {
            if (abs((float) $entry[0] - 40.0) < 0.001) {
                $found = true;
            }
            $this->assertNotEquals(50, (float) $entry[0]);
            $this->assertNotEquals(80, (float) $entry[0]);
            $this->assertNotEquals(100, (float) $entry[0]);
            $this->assertNotEquals(30, (float) $entry[0]);
        }
        $this->assertTrue($found);
    }

    public function testInvalidOutOfRangeAndForeignShopOrdersAreExcluded()
    {
        Fixture::seedCatalog();
        Fixture::setEmployeeRange('2020-01-06', '2020-01-06');
        Fixture::insertOrder(array('total_paid_tax_excl' => 10, 'conversion_rate' => 1));
        Fixture::insertOrder(array('valid' => 0, 'total_paid_tax_excl' => 999));
        Fixture::insertOrder(array('invoice_date' => '2020-01-05 12:00:00', 'date_add' => '2020-01-05 12:00:00', 'total_paid_tax_excl' => 999));
        Fixture::insertOrder(array('id_shop' => 2, 'total_paid_tax_excl' => 999));
        $html = $this->runHook();
        $this->assertContains('PRICE(EUR:10.00)', $html);
        $this->assertNotContains('PRICE(EUR:999.00)', $html);
    }

    public function testSharedOrdersFromTheSameGroupAreIncluded()
    {
        Fixture::seedCatalog();
        Fixture::setEmployeeRange('2020-01-06', '2020-01-06');
        Fixture::insertOrder(array('total_paid_tax_excl' => 10, 'id_shop' => 1));
        Fixture::insertOrder(array('total_paid_tax_excl' => 40, 'id_shop' => 2));
        Shop::setShareOrder(false);
        $html = $this->runHook();
        $this->assertContains('PRICE(EUR:10.00)', $html);
        $this->assertNotContains('PRICE(EUR:40.00)', $html);
        $this->truncateAll();
        Fixture::seedCatalog();
        Fixture::setEmployeeRange('2020-01-06', '2020-01-06');
        Fixture::insertOrder(array('total_paid_tax_excl' => 10, 'id_shop' => 1));
        Fixture::insertOrder(array('total_paid_tax_excl' => 40, 'id_shop' => 2));
        Shop::setShareOrder(true);
        Context::getContext()->clearLocaleLog();
        $this->runHook();
        $found50 = false;
        foreach (Context::getContext()->getLocaleLog() as $entry) {
            if (is_numeric($entry[0]) && abs((float) $entry[0] - 50.0) < 0.001) {
                $found50 = true;
            }
        }
        $this->assertTrue($found50);
    }

    public function testVisitsRegistrationsAndRatesForADay()
    {
        Fixture::seedCatalog();
        Fixture::setEmployeeRange('2020-01-10', '2020-01-10');
        Fixture::insertConnection('2020-01-10 08:00:00', 1, 1);
        Fixture::insertConnection('2020-01-10 09:00:00', 1, 1);
        Fixture::insertConnection('2020-01-10 10:00:00', 1, 2);
        Fixture::insertConnection('2020-01-10 11:00:00', 1, 3);
        Fixture::insertConnection('2020-01-10 12:00:00', 1, 4);
        Fixture::insertCustomer('2020-01-10 10:00:00');
        Fixture::insertCustomer('2020-01-10 11:00:00');
        Fixture::insertOrder(array(
            'invoice_date' => '2020-01-10 12:00:00',
            'date_add' => '2020-01-10 12:00:00',
            'total_paid_tax_excl' => 10,
            'lines' => array(array('product_quantity' => 1)),
        ));
        $html = $this->runHook();
        $this->assertContains('40 %', $html);
        $this->assertContains('20 %', $html);
        $this->assertContains('PRICE(EUR:10.00)', $html);
    }

    public function testDayWithVisitsAndNoOrdersShowsDashesNotWarnings()
    {
        Fixture::seedCatalog();
        Fixture::setEmployeeRange('2020-01-10', '2020-01-10');
        Fixture::insertConnection('2020-01-10 08:00:00');
        Fixture::insertConnection('2020-01-10 09:00:00');
        Fixture::insertConnection('2020-01-10 10:00:00');
        $html = $this->runHook();
        $this->assertContains('>-<', $html);
    }

    public function testCreationDateOmitsEmptyDaysBeforeTheShopExisted()
    {
        $script = dirname(__FILE__) . '/scripts/child_creation_date.php';
        $result = $this->runChildPhp($script);
        $this->assertSame(0, $result['exit'], $result['stderr'] . $result['stdout']);
        $diag = '/\b(Notice|Warning|Deprecated|Strict Standards|Parse error|Fatal error|Recoverable fatal error)\b/';
        $this->assertNotRegExp($diag, $result['stdout'] . $result['stderr']);
        $this->assertContains('2020-04-02', $result['stdout']);
        $this->assertContains('2020-04-03', $result['stdout']);
        $this->assertNotContains('2020-04-01', $result['stdout']);
    }

    public function testBoughtItemsSumQuantitiesAcrossOrderLines()
    {
        Fixture::seedCatalog();
        Fixture::setEmployeeRange('2020-01-06', '2020-01-06');
        Fixture::insertOrder(array(
            'lines' => array(
                array('product_quantity' => 2),
                array('product_quantity' => 3),
            ),
        ));
        $html = $this->runHook();
        $this->assertRegExp('/<td class="text-center">5<\/td>/', $html);
    }

    public function testAdminLinkInFormActionIsEscaped()
    {
        Fixture::seedCatalog();
        Link::$overrideAdminLink = 'http://shop.test/a?q="1"';
        $html = $this->runHook();
        $this->assertContains('http://shop.test/a?q=&quot;1&quot;', $html);
        $this->assertNotContains('http://shop.test/a?q="1"', $html);
    }
}
