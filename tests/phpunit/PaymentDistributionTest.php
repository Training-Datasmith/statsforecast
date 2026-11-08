<?php

class PaymentDistributionTest extends DatabaseCase
{
    public function testPaymentTotalsAverageAndDescendingOrder()
    {
        Fixture::seedCatalog();
        Fixture::setEmployeeRange('2020-01-06', '2020-01-07');
        $r1 = 'REF1';
        $r2 = 'REF2';
        Fixture::insertOrder(array('reference' => $r1, 'invoice_date' => '2020-01-06 12:00:00', 'date_add' => '2020-01-06 12:00:00'));
        Fixture::insertOrder(array('reference' => $r2, 'invoice_date' => '2020-01-07 12:00:00', 'date_add' => '2020-01-07 12:00:00'));
        Fixture::insertPayment($r1, 'Bank wire', 80, 2, '2020-01-06 12:00:00');
        Fixture::insertPayment($r2, 'Card', 10, 1, '2020-01-07 12:00:00');
        $html = $this->runHook();
        $wire = strpos($html, 'Bank wire');
        $card = strpos($html, 'Card');
        $this->assertTrue($wire !== false && $card !== false && $wire < $card);
    }

    public function testDistinctOrderReferenceCountsOnce()
    {
        Fixture::seedCatalog();
        Fixture::setEmployeeRange('2020-01-06', '2020-01-06');
        Fixture::insertOrder(array('reference' => 'REF1'));
        Fixture::insertPayment('REF1', 'Bank wire', 10, 1, '2020-01-06 12:00:00');
        Fixture::insertPayment('REF1', 'Bank wire', 15, 1, '2020-01-06 13:00:00');
        $html = $this->runHook();
        $pay = $this->sectionAfter($html, 'Payment distribution');
        $this->assertRegExp('/Bank wire[\s\S]*?<td class="text-center">1<\/td>/', $pay);
        $this->assertContains('PRICE(EUR:25.00)', $pay);
        $this->assertNotContains('PRICE(EUR:12.50)', $pay);
    }

    public function testPaymentsOutsideTheEmployeeRangeAreExcluded()
    {
        Fixture::seedCatalog();
        Fixture::setEmployeeRange('2020-01-06', '2020-01-07');
        Fixture::insertOrder(array('reference' => 'REF1', 'invoice_date' => '2020-01-06 12:00:00', 'date_add' => '2020-01-06 12:00:00'));
        Fixture::insertPayment('REF1', 'Old', 10, 1, '2019-01-01 12:00:00');
        $html = $this->runHook();
        $this->assertNotContains('Old', $html);
    }

    public function testReferenceContainingADoubleQuoteStillShowsItsPayment()
    {
        Fixture::seedCatalog();
        Fixture::setEmployeeRange('2020-01-06', '2020-01-07');
        Fixture::insertOrder(array('reference' => 'A"B', 'invoice_date' => '2020-01-06 12:00:00', 'date_add' => '2020-01-06 12:00:00'));
        Fixture::insertOrder(array('reference' => 'REF2', 'invoice_date' => '2020-01-07 12:00:00', 'date_add' => '2020-01-07 12:00:00'));
        Fixture::insertPayment('A"B', 'Wire', 12, 1, '2020-01-06 12:00:00');
        $html = $this->runHook();
        $this->assertContains('Wire', $html);
        $this->assertContains('PRICE(EUR:12.00)', $html);
    }
}
