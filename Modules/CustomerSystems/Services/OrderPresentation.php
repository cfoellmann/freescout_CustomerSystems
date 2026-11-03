<?php

namespace Modules\CustomerSystems\Services;

class OrderPresentation
{
    public static function compact($text, $limit)
    {
        $characters = preg_split('//u', (string) $text, -1, PREG_SPLIT_NO_EMPTY);
        if ($characters === false) { return ''; }
        return count($characters) > $limit
            ? implode('', array_slice($characters, 0, $limit - 1)).'…' : (string) $text;
    }

    public static function status(array $order)
    {
        // Colour by Shopware's stable state key, never by a translated display name.
        $states = [
            'open' => ['label' => 'Offen', 'class' => 'label-info'],
            'in_progress' => ['label' => 'In Arb.', 'class' => 'label-warning'],
            'completed' => ['label' => 'Abgeschl.', 'class' => 'label-success'],
            'cancelled' => ['label' => 'Storniert', 'class' => 'label-danger'],
        ];
        return $states[$order['status_key'] ?? '']
            ?? ['label' => self::compact($order['status'], 9), 'class' => 'label-default'];
    }
}
