<?php

namespace App\Services;

class RevenueService {
    /**
     * @param mixed $targetData
     *
     * @return array<string, array{new_connections: int, revenue: float, connected_power: float, energy_per_month: float, average_revenue_per_month: float}>
     */
    public function fetchTargets($targetData): array {
        $formattedTarget = [];
        if (is_object($targetData) && count($targetData) >= 1) {
            foreach ($targetData as $targets) {
                foreach ($targets->subTargets as $subTarget) {
                    if (isset($formattedTarget[$subTarget->connectionType->name])) {
                        $formattedTarget[$subTarget->connectionType->name] = [
                            'new_connections' => $formattedTarget[$subTarget->connectionType->name]['new_connections'] +
                                $subTarget->new_connections,
                            'revenue' => $formattedTarget[$subTarget->connectionType->name]['revenue'] +
                                $subTarget->revenue,
                            'connected_power' => $formattedTarget[$subTarget->connectionType->name]['connected_power'] +
                                $subTarget->connected_power,
                            'energy_per_month' => $formattedTarget[$subTarget->connectionType->name]['energy_per_month'] +
                                $subTarget->energy_per_month,
                            'average_revenue_per_month' => $formattedTarget[$subTarget->connectionType->name]['average_revenue_per_month'] +
                                $subTarget->average_revenue_per_month,
                        ];
                    } else {
                        $formattedTarget[$subTarget->connectionType->name] = [
                            'new_connections' => $subTarget->new_connections,
                            'revenue' => $subTarget->revenue,
                            'connected_power' => $subTarget->connected_power,
                            'energy_per_month' => $subTarget->energy_per_month,
                            'average_revenue_per_month' => $subTarget->average_revenue_per_month,
                        ];
                    }
                }
                unset($targets->subTargets);
            }
        }

        return $formattedTarget;
    }
}
