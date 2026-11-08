<?php

class ModuleGraph
{
    public static function getDateBetween($employee = null)
    {
        if ($employee === null) {
            $employee = Context::getContext()->employee;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $employee->stats_date_from)
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $employee->stats_date_to)) {
            throw new RuntimeException('invalid employee stats date range');
        }

        return ' \'' . pSQL($employee->stats_date_from) . ' 00:00:00\' AND \'' . pSQL($employee->stats_date_to) . ' 23:59:59\' ';
    }
}
