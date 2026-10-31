<?php

namespace Modules\CustomerSystems\Services;

use RuntimeException;

class Shopware6Client
{
    private $request;

    public function __construct(callable $request)
    {
        $this->request = $request;
    }

    public function testConnection()
    {
        $this->search([ 'limit' => 1, 'includes' => ['customer' => ['id']], 'total-count-mode' => 0 ]);
    }

    public function findByEmail($email)
    {
        $email = trim((string) $email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [];
        }
        $response = $this->search([
            'limit' => ShopwareClient::MAX_MATCHES + 1,
            'total-count-mode' => 1,
            'filter' => [['type' => 'equals', 'field' => 'email', 'value' => $email]],
            'associations' => ['defaultBillingAddress' => ['associations' => ['country' => new \stdClass()]]],
            'includes' => [
                'customer' => ['id', 'customerNumber', 'salesChannelId', 'firstName', 'lastName', 'email', 'defaultBillingAddress'],
                'customer_address' => ['company', 'phoneNumber', 'street', 'zipcode', 'city', 'country'],
                'country' => ['name', 'iso', 'translated'],
            ],
        ]);
        if (!isset($response['total']) || !is_numeric($response['total'])) {
            throw new RuntimeException('invalid_response');
        }
        if ((int) $response['total'] > ShopwareClient::MAX_MATCHES || count($response['data']) > ShopwareClient::MAX_MATCHES) {
            throw new RuntimeException('too_many_matches');
        }
        $customers = [];
        foreach ($response['data'] as $row) {
            if (!is_array($row) || !is_string($row['email'] ?? null)
                || strcasecmp(trim($row['email']), $email) !== 0
                || !is_string($row['id'] ?? null) || !preg_match('/^[a-f0-9]{32}$/i', $row['id'])) {
                throw new RuntimeException('invalid_response');
            }
            $address = is_array($row['defaultBillingAddress'] ?? null) ? $row['defaultBillingAddress'] : [];
            $country = is_array($address['country'] ?? null) ? $address['country'] : [];
            $text = function ($value) { return is_scalar($value) ? trim((string) $value) : ''; };
            $customers[$row['id']] = [
                'id' => strtolower($row['id']), 'number' => $text($row['customerNumber'] ?? ''),
                'shop_id' => $text($row['salesChannelId'] ?? ''),
                'name' => trim($text($row['firstName'] ?? '').' '.$text($row['lastName'] ?? '')),
                'email' => $text($row['email']), 'company' => $text($address['company'] ?? ''),
                'phone' => $text($address['phoneNumber'] ?? ''), 'street' => $text($address['street'] ?? ''),
                'postcode' => $text($address['zipcode'] ?? ''), 'city' => $text($address['city'] ?? ''),
                'country' => $text($country['translated']['name'] ?? $country['name'] ?? $country['iso'] ?? ''),
            ];
        }
        return array_values($customers);
    }

    private function search(array $criteria)
    {
        $response = call_user_func($this->request, 'search/customer', $criteria);
        if (!is_array($response) || !isset($response['data']) || !is_array($response['data'])) {
            throw new RuntimeException('invalid_response');
        }
        return $response;
    }
}
