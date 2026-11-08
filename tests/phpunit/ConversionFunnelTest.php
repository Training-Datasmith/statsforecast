<?php

class ConversionFunnelTest extends DatabaseCase
{
    public function testFunnelCountsDistinctVisitorsAccountsCartsAndOrders()
    {
        Fixture::seedCatalog();
        Fixture::setEmployeeRange('2020-08-01', '2020-08-31');
        Fixture::insertConnection('2020-08-10 10:00:00', 1, 1);
        Fixture::insertConnection('2020-08-10 11:00:00', 1, 1);
        Fixture::insertGuest(2, 5);
        Fixture::insertConnection('2020-08-10 12:00:00', 1, 2);
        Fixture::insertGuest(3, 6);
        Fixture::insertConnection('2020-08-10 13:00:00', 1, 3);
        Fixture::insertConnection('2020-08-10 14:00:00', 2, 4);
        Fixture::insertCart(array('date_add' => '2020-08-11 10:00:00', 'date_upd' => '2020-08-11 10:00:00', 'products' => array(array())));
        Fixture::insertCart(array('date_add' => '2020-08-12 10:00:00', 'date_upd' => '2020-08-12 10:00:00', 'id_address_invoice' => 9, 'products' => array(array())));
        Fixture::insertOrder(array('invoice_date' => '2020-08-15 12:00:00', 'date_add' => '2020-08-15 12:00:00', 'total_paid_tax_excl' => 20));
        $html = $this->runHook();
        $this->assertContains('66.67 %', $html);
        $this->assertContains('67 %', $html);
        $this->assertContains('50 %', $html);
    }

    public function testFunnelOrdersFollowCreationDateWhileRevenueFollowsInvoiceDate()
    {
        Fixture::seedCatalog();
        Fixture::setEmployeeRange('2020-08-01', '2020-08-31');
        Fixture::insertOrder(array('invoice_date' => '2020-08-10 12:00:00', 'date_add' => '2020-08-10 12:00:00', 'total_paid_tax_excl' => 20));
        Fixture::insertOrder(array('invoice_date' => '2020-08-11 12:00:00', 'date_add' => '2020-07-01 12:00:00', 'total_paid_tax_excl' => 30));
        Fixture::insertOrder(array('invoice_date' => '2020-09-01 12:00:00', 'date_add' => '2020-08-12 12:00:00', 'total_paid_tax_excl' => 40));
        Fixture::insertConnection('2020-08-10 12:00:00');
        $html = $this->runHook();
        $this->assertRegExp('/<p>2<\/p>/', $html);
        $found50 = false;
        foreach (Context::getContext()->getLocaleLog() as $entry) {
            if (is_numeric($entry[0]) && abs((float) $entry[0] - 50.0) < 0.001) {
                $found50 = true;
            }
        }
        $this->assertTrue($found50);
    }

    public function testOnlyCartsWithProductsCountAndInvoiceAddressMakesThemFull()
    {
        Fixture::seedCatalog();
        Fixture::setEmployeeRange('2020-08-01', '2020-08-31');
        Fixture::insertCart(array('products' => array(array()), 'date_add' => '2020-08-10 10:00:00', 'date_upd' => '2020-08-10 10:00:00'));
        Fixture::insertCart(array('products' => array(array()), 'id_address_invoice' => 9, 'date_add' => '2020-08-11 10:00:00', 'date_upd' => '2020-08-11 10:00:00'));
        Fixture::insertCart(array('products' => array(), 'id_address_invoice' => 9, 'date_add' => '2020-08-12 10:00:00', 'date_upd' => '2020-08-12 10:00:00'));
        Fixture::insertCart(array('products' => array(array()), 'date_add' => '2020-07-01 10:00:00', 'date_upd' => '2020-07-01 10:00:00'));
        Fixture::insertConnection('2020-08-10 10:00:00');
        $html = $this->runHook();
        $conv = $this->sectionAfter($html, 'Conversion');
        $this->assertRegExp('/Full carts[\s\S]*?<p>1<\/p>/', $conv);
        $this->assertRegExp('/Carts[\s\S]*?<p>2<\/p>/', $conv);
    }

    public function testZeroVisitorsDoNotWarnAndShowZeroPercents()
    {
        Fixture::seedCatalog();
        Fixture::setEmployeeRange('2020-08-01', '2020-08-31');
        $this->runHook();
        $found0 = false;
        foreach (Context::getContext()->getLocaleLog() as $entry) {
            if (is_numeric($entry[0]) && abs((float) $entry[0]) < 0.001) {
                $found0 = true;
            }
        }
        $this->assertTrue($found0);
    }
}
