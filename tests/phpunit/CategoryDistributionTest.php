<?php

class CategoryDistributionTest extends DatabaseCase
{
    private function seedTwoCategoryOrders()
    {
        Fixture::seedCatalog();
        Fixture::setEmployeeRange('2020-01-06', '2020-01-06');
        Fixture::insertOrder(array(
            'invoice_date' => '2020-01-06 15:00:00',
            'date_add' => '2020-01-06 15:00:00',
            'total_paid_tax_excl' => 10,
            'conversion_rate' => 1,
            'lines' => array(array(
                'product_id' => 1,
                'product_quantity' => 1,
                'unit_price_tax_excl' => 10,
                'product_price' => 99,
            )),
        ));
        Fixture::insertOrder(array(
            'invoice_date' => '2020-01-06 16:00:00',
            'date_add' => '2020-01-06 16:00:00',
            'total_paid_tax_excl' => 60,
            'conversion_rate' => 2,
            'lines' => array(array(
                'product_id' => 2,
                'product_quantity' => 2,
                'unit_price_tax_excl' => 30,
                'product_price' => 1,
            )),
        ));
    }

    public function testCategoryRevenueUsesUnitPriceTaxExclAndSortsDescending()
    {
        $this->seedTwoCategoryOrders();
        $html = $this->runHook();
        $posGarden = strpos($html, 'Garden');
        $posHome = strpos($html, 'Home');
        $this->assertTrue($posGarden !== false && $posHome !== false && $posGarden < $posHome);
        $cat = $this->sectionAfter($html, 'Category distribution');
        $this->assertContains('66.7%', $cat);
        $this->assertContains('33.3%', $cat);
        $this->assertContains('75.0%', $cat);
        $this->assertContains('25.0%', $cat);
        $this->assertNotContains('PRICE(EUR:99.00)', $cat);
        $this->assertNotContains('PRICE(EUR:60.00)', $cat);
        $expectedLogAmounts = array(30.0, 10.0, 15.0, 10.0);
        foreach ($expectedLogAmounts as $expected) {
            $found = false;
            foreach (Context::getContext()->getLocaleLog() as $entry) {
                if (is_numeric($entry[0]) && abs((float) $entry[0] - $expected) < 0.001) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, 'expected formatPrice log amount ' . $expected);
        }
    }

    public function testMissingCategoryNameIsShownAsUnknown()
    {
        Fixture::seedCatalog();
        Fixture::setEmployeeRange('2020-01-06', '2020-01-06');
        Fixture::setCategoryDefault(1, 4242);
        Fixture::insertOrder(array(
            'lines' => array(array('product_id' => 1, 'product_quantity' => 1, 'unit_price_tax_excl' => 10)),
        ));
        $html = $this->runHook();
        $this->assertContains('Unknown', $html);
    }

    public function testProductAndSalesPercentagesUseRowQuantitiesAndVentil()
    {
        $this->seedTwoCategoryOrders();
        $html = $this->runHook();
        $cat = $this->sectionAfter($html, 'Category distribution');
        $this->assertContains('66.7%', $cat);
        $this->assertContains('33.3%', $cat);
        $this->assertContains('75.0%', $cat);
        $this->assertContains('25.0%', $cat);
    }

    public function testZeroQuantityLineDoesNotWarnAndPrintsZeroPercent()
    {
        Fixture::seedCatalog();
        Fixture::setEmployeeRange('2020-01-06', '2020-01-06');
        Fixture::insertOrder(array(
            'lines' => array(array('product_quantity' => 0, 'unit_price_tax_excl' => 10, 'product_id' => 1)),
        ));
        $html = $this->runHook();
        $this->assertContains('0%', $html);
    }
}
