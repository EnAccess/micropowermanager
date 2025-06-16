<?php

namespace App\Services;

class PeriodService {
    /**
     * @param                   $startDate
     * @param                   $endDate
     * @param                   $interval
     * @param                   $initialData
     * @param (int|int[])[]|int $initialData
     *
     * @return array
     *
     * @throws \Exception
     */
    public function generatePeriodicList(string $startDate, string $endDate, string $interval, $initialData): array {
        $result = [];
        $begin = date_create($startDate);
        $end = date_create($endDate);

        $daysDifference = $end->diff($begin)->days;

        if ($interval === 'weekly' && $daysDifference % 7 !== 0) {
            $end->add(new \DateInterval('P'.($daysDifference % 7).'D'));
        }

        if ($end->diff($begin)->days % 365 === 0) {
            $end->add(new \DateInterval('P1D'));
        }
        $end->setTime(0, 0, 1);

        if ($interval === 'weekly') {
            $i = new \DateInterval('P1W');
        } else {
            // Make sure we don't overflow (and hence skip) a month if this is called with end date 31st.
            // See: https://www.php.net/manual/en/datetime.examples-arithmetic.php
            $begin = (new \DateTime($startDate))->modify('first day of this month');
            $end = (new \DateTime($endDate))->modify('last day of this month')->setTime(0, 0, 1);
            $i = new \DateInterval('P1M');
        }
        $period = new \DatePeriod($begin, $i, $end);

        foreach ($period as $p) {
            if ($interval === 'weekMonth') {
                $mPeriod = new \DatePeriod(
                    date_create($p->format('o-m-1')),
                    new \DateInterval('P1W'),
                    date_create(date('Y-m-t', strtotime($p->format('o-m-1'))))
                );
                foreach ($mPeriod as $mP) {
                    $result[$p->format('o-m')][$mP->format('o-W')] = $initialData;
                }
            } elseif ($interval === 'weekly') {
                $result[$p->format('o-W')] = $initialData;
            } elseif ($interval === 'monthly') {
                $result[$p->format('Y-m')] = $initialData;
            } else {
                $result[$p->format('o-W')] = $initialData;
            }
        }

        return $result;
    }
}
