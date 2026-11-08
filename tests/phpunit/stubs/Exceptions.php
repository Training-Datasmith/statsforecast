<?php

class StatsforecastRedirect extends Exception
{
    public function __construct($url)
    {
        parent::__construct($url);
    }
}
