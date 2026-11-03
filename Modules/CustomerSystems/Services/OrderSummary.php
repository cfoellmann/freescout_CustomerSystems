<?php

namespace Modules\CustomerSystems\Services;

class OrderSummary
{
    public static function latest(array $rows)
    {
        $orders = [];
        foreach ($rows as $row) {
            if (!is_string($row['id'] ?? null) || !is_scalar($row['number'] ?? null)
                || !is_string($row['ordered_at'] ?? null) || strlen($row['ordered_at']) > 80) {
                throw new \RuntimeException('invalid_response');
            }
            try { $date = new \DateTimeImmutable($row['ordered_at']); }
            catch (\Exception $error) { throw new \RuntimeException('invalid_response'); }
            if ($row['ordered_at'] === '') { throw new \RuntimeException('invalid_response'); }
            $orders[$row['id']] = [
                'id' => $row['id'], 'number' => trim((string) $row['number']),
                'date' => $date->format('d.m.Y'),
                'status' => is_scalar($row['status'] ?? null) && trim((string) $row['status']) !== ''
                    ? trim((string) $row['status']) : 'Unbekannt',
                '_timestamp' => $date->getTimestamp(),
            ];
        }
        usort($orders, function ($left, $right) {
            return ($right['_timestamp'] <=> $left['_timestamp']) ?: strcmp($right['id'], $left['id']);
        });
        $orders = array_slice($orders, 0, 5);
        foreach ($orders as &$order) { unset($order['_timestamp']); }
        return $orders;
    }
}
