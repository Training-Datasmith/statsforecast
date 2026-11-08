<?php

class Locale
{
    public $formatPriceLog = array();

    public function formatPrice($amount, $iso)
    {
        $this->formatPriceLog[] = array($amount, $iso);

        return 'PRICE(' . $iso . ':' . sprintf('%.2f', (float) $amount) . ')';
    }
}
