<?php

class CurrencyAndZoneTest extends DatabaseCase
{
    public function testZonesOrderByConvertedSalesWithPercents()
    {
        Fixture::seedCatalog();
        Fixture::addCountryZone(2, 2);
        Fixture::addAddress(2, 2);
        Fixture::setEmployeeRange('2020-01-06', '2020-01-31');
        Fixture::insertOrder(array('id_address_invoice' => 1, 'total_paid_tax_excl' => 25));
        Fixture::insertOrder(array('id_address_invoice' => 2, 'total_paid_tax_excl' => 25));
        Fixture::insertOrder(array('id_address_invoice' => 2, 'total_paid_tax_excl' => 25));
        Fixture::insertOrder(array('id_address_invoice' => 2, 'total_paid_tax_excl' => 25));
        $html = $this->runHook();
        $america = strpos($html, 'America');
        $europe = strpos($html, 'Europe');
        $this->assertTrue($america !== false && $europe !== false && $america < $europe);
        $this->assertContains('75.0%', $html);
        $this->assertContains('25.0%', $html);
    }

    public function testMissingZoneNameRendersUndefined()
    {
        Fixture::seedCatalog();
        Fixture::addCountryZone(99, null);
        Fixture::addAddress(99, 99);
        Fixture::setEmployeeRange('2020-01-06', '2020-01-06');
        Fixture::insertOrder(array('id_address_invoice' => 99, 'total_paid_tax_excl' => 10));
        $html = $this->runHook();
        $this->assertContains('Undefined', $html);
    }

    public function testCurrenciesOrderByConvertedSalesWithPercents()
    {
        Fixture::seedCatalog();
        Fixture::addCurrency(2, 'Dollar');
        Fixture::setEmployeeRange('2020-01-06', '2020-01-07');
        Fixture::insertOrder(array('id_currency' => 1, 'total_paid_tax_excl' => 75, 'conversion_rate' => 1));
        Fixture::insertOrder(array('id_currency' => 2, 'total_paid_tax_excl' => 50, 'conversion_rate' => 2));
        $html = $this->runHook();
        $euroPos = strpos($html, 'Euro');
        $dollarPos = strpos($html, 'Dollar');
        $this->assertTrue($euroPos !== false && $dollarPos !== false && $euroPos < $dollarPos);
        $this->assertContains('75.0%', $html);
        $this->assertContains('25.0%', $html);
    }

    public function testZoneCookieFiltersCategoryPaymentAndCurrencyOnly()
    {
        Fixture::seedCatalog();
        Fixture::addCountryZone(2, 2);
        Fixture::addAddress(2, 2);
        Fixture::setEmployeeRange('2020-01-06', '2020-01-07');
        Fixture::insertOrder(array(
            'id_address_invoice' => 1,
            'reference' => 'R1',
            'total_paid_tax_excl' => 30,
            'lines' => array(array('product_id' => 1, 'unit_price_tax_excl' => 30, 'product_quantity' => 1)),
        ));
        Fixture::insertOrder(array(
            'id_address_invoice' => 2,
            'reference' => 'R2',
            'total_paid_tax_excl' => 70,
            'invoice_date' => '2020-01-07 12:00:00',
            'date_add' => '2020-01-07 12:00:00',
            'lines' => array(array('product_id' => 2, 'unit_price_tax_excl' => 30, 'product_quantity' => 1)),
        ));
        Fixture::insertPayment('R1', 'Check', 30, 1, '2020-01-06 12:00:00');
        Fixture::insertPayment('R2', 'Card', 70, 1, '2020-01-07 12:00:00');
        $_POST['submitIdZone'] = 1;
        $_POST['stats_id_zone'] = '2';
        $html = $this->runHook();
        $this->assertSame(2, (int) Context::getContext()->cookie->stats_id_zone);
        $cat = $this->sectionAfter($html, 'Category distribution');
        $this->assertContains('Garden', $cat);
        $this->assertNotContains('Home', $cat);
        $pay = $this->sectionAfter($html, 'Payment distribution');
        $this->assertContains('Card', $pay);
        $this->assertNotContains('Check', $pay);
        $this->assertContains('selected="selected"', $html);
    }

    public function testSubmittedZoneIdIsCastToInteger()
    {
        Fixture::seedCatalog();
        Fixture::addCountryZone(2, 2);
        Fixture::addAddress(2, 2);
        Fixture::setEmployeeRange('2020-01-06', '2020-01-07');
        Fixture::insertOrder(array('id_address_invoice' => 2, 'total_paid_tax_excl' => 70, 'lines' => array(array('product_id' => 2))));
        $_POST['submitIdZone'] = 1;
        $_POST['stats_id_zone'] = '2; DROP TABLE ps_zone';
        $this->runHook();
        $this->assertSame(2, (int) Context::getContext()->cookie->stats_id_zone);
        $zones = Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'zone');
        $this->assertSame('2', $zones);
    }
}
