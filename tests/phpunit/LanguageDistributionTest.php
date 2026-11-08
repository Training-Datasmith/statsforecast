<?php

class LanguageDistributionTest extends DatabaseCase
{
    public function testLanguageShareAndGrowthForWholeAmounts()
    {
        Fixture::seedCatalog();
        Fixture::addLanguage(2, 'fr', 1);
        Fixture::addLanguage(3, 'it', 0);
        Fixture::setEmployeeRange('2020-06-01', '2020-06-30');
        Fixture::insertOrder(array('id_lang' => 1, 'invoice_date' => '2020-06-10 12:00:00', 'date_add' => '2020-06-10 12:00:00', 'total_paid_tax_excl' => 200));
        Fixture::insertOrder(array('id_lang' => 2, 'invoice_date' => '2020-06-10 12:00:00', 'date_add' => '2020-06-10 12:00:00', 'total_paid_tax_excl' => 50));
        Fixture::insertOrder(array('id_lang' => 1, 'invoice_date' => '2020-05-15 12:00:00', 'date_add' => '2020-05-15 12:00:00', 'total_paid_tax_excl' => 100));
        Fixture::insertOrder(array('id_lang' => 2, 'invoice_date' => '2020-05-20 12:00:00', 'date_add' => '2020-05-20 12:00:00', 'total_paid_tax_excl' => 200));
        $html = $this->runHook();
        $posEn = strpos($html, '>en<');
        $posFr = strpos($html, '>fr<');
        $this->assertTrue($posEn !== false && $posFr !== false && $posEn < $posFr);
        $this->assertContains('+100.0%', $html);
        $this->assertContains('-75.0%', $html);
        $this->assertContains('arrow_up.png', $html);
        $this->assertContains('arrow_down.png', $html);
        $this->assertContains('alt_row', $html);
        $this->assertContains('80.0%', $html);
        $this->assertContains('20.0%', $html);
        $this->assertNotContains('>it<', $html);
    }

    public function testZeroPreviousSalesRendersInfinityAndUpArrow()
    {
        Fixture::seedCatalog();
        Db::getInstance()->getLink()->query('DELETE FROM ' . _DB_PREFIX_ . 'lang');
        Db::getInstance()->getLink()->query('DELETE FROM ' . _DB_PREFIX_ . 'lang_shop');
        Fixture::addLanguage(1, 'de', 1);
        Fixture::setEmployeeRange('2020-06-01', '2020-06-30');
        Fixture::insertOrder(array('id_lang' => 1, 'invoice_date' => '2020-06-10 12:00:00', 'date_add' => '2020-06-10 12:00:00', 'total_paid_tax_excl' => 10));
        $html = $this->runHook();
        $this->assertContains('&#x221e;', $html);
        $this->assertContains('arrow_up.png', $html);
    }

    public function testInactiveLanguageIsLeftOut()
    {
        Fixture::seedCatalog();
        Fixture::addLanguage(3, 'it', 0);
        Fixture::setEmployeeRange('2020-06-01', '2020-06-30');
        Fixture::insertOrder(array('id_lang' => 1, 'invoice_date' => '2020-06-10 12:00:00', 'total_paid_tax_excl' => 10));
        Fixture::insertOrder(array('id_lang' => 3, 'invoice_date' => '2020-06-11 12:00:00', 'total_paid_tax_excl' => 999));
        $html = $this->runHook();
        $this->assertContains('>en<', $html);
        $this->assertNotContains('>it<', $html);
        $found = false;
        foreach (Context::getContext()->getLocaleLog() as $entry) {
            if (is_numeric($entry[0]) && abs((float) $entry[0] - 1009.0) < 0.001) {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    public function testNoActiveLanguageSkipsTheLanguageTable()
    {
        Fixture::seedCatalog();
        Db::getInstance()->getLink()->query('UPDATE ' . _DB_PREFIX_ . 'lang SET active = 0');
        Fixture::setEmployeeRange('2020-06-01', '2020-06-30');
        Fixture::insertOrder(array('invoice_date' => '2020-06-10 12:00:00', 'total_paid_tax_excl' => 10));
        $html = $this->runHook();
        $this->assertNotContains('>en<', $html);
    }
}
