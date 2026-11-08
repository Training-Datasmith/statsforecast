<?php

class AttributeDistributionTest extends DatabaseCase
{
    public function testSoldAttributeQuantitiesUseTheContextLanguage()
    {
        Fixture::seedCatalog();
        Fixture::seedAttributes();
        Context::getContext()->language->id = 1;
        Fixture::setEmployeeRange('2020-01-06', '2020-01-06');
        Fixture::insertOrder(array(
            'lines' => array(
                array('product_attribute_id' => 100, 'product_quantity' => 2),
                array('product_attribute_id' => 101, 'product_quantity' => 5),
                array('product_attribute_id' => 0, 'product_quantity' => 9),
            ),
        ));
        $html = $this->runHook();
        $section = $this->sectionAfter($html, 'Attribute distribution');
        $this->assertContains('Size', $section);
        $this->assertContains('Large', $section);
        $this->assertContains('Color', $section);
        $this->assertContains('Red', $section);
        $this->assertNotContains('WRONG', $section);
        $this->assertNotContains('>9<', $section);
    }
}
