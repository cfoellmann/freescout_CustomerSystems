<?php

namespace Modules\CustomerSystems\Services;

use RuntimeException;

class ShopwareClient
{
    private $request;
    const MAX_MATCHES = 5;

    public function __construct(callable $request)
    {
        $this->request = $request;
    }

    public function findByEmail($email)
    {
        $email = trim((string) $email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [];
        }
        $list = $this->get('customers', [
            'filter' => [['property' => 'email', 'expression' => '=', 'value' => $email]],
            'limit' => self::MAX_MATCHES + 1,
            'start' => 0,
        ]);
        if (!is_array($list['data']) || !isset($list['total']) || !is_numeric($list['total'])) {
            throw new RuntimeException('invalid_response');
        }
        if ((int) $list['total'] > self::MAX_MATCHES || count($list['data']) > self::MAX_MATCHES) {
            throw new RuntimeException('too_many_matches');
        }
        $customers = [];
        foreach ($list['data'] as $row) {
            if (!is_array($row) || !$this->sameEmail($row, $email)) {
                throw new RuntimeException('invalid_response');
            }
            $id = isset($row['id']) ? (string) $row['id'] : '';
            if (!ctype_digit($id) || (int) $id < 1) {
                throw new RuntimeException('invalid_response');
            }
            $detail = $this->get('customers/'.$id, []);
            if (!is_array($detail['data']) || !$this->sameEmail($detail['data'], $email)
                || (string) ($detail['data']['id'] ?? '') !== $id) {
                throw new RuntimeException('invalid_response');
            }
            $customers[$id] = $this->normalize($detail['data']);
        }
        return array_values($customers);
    }

    public function testConnection()
    {
        $response = $this->get('customers', ['limit' => 1, 'start' => 0]);
        if (!is_array($response['data'])) {
            throw new RuntimeException('invalid_response');
        }
    }

    private function get($path, array $query)
    {
        $response = call_user_func($this->request, $path, $query);
        if (!is_array($response) || ($response['success'] ?? false) !== true
            || !array_key_exists('data', $response)) {
            throw new RuntimeException('invalid_response');
        }
        return $response;
    }

    private function sameEmail(array $customer, $email)
    {
        return isset($customer['email']) && is_string($customer['email'])
            && strcasecmp(trim($customer['email']), $email) === 0;
    }

    private function normalize(array $customer)
    {
        $billing = $customer['defaultBillingAddress'] ?? $customer['billing'] ?? [];
        $billing = is_array($billing) ? $billing : [];
        $country = is_array($billing['country'] ?? null) ? $billing['country'] : [];
        $text = function ($value) {
            return is_scalar($value) ? trim((string) $value) : '';
        };
        // Explicit allowlist: never return raw API data (password hashes, bank details, etc.).
        return [
            'id' => $text($customer['id'] ?? ''),
            'number' => $text($customer['number'] ?? $billing['number'] ?? ''),
            'shop_id' => $text($customer['shopId'] ?? ''),
            'name' => trim($text($customer['firstname'] ?? $billing['firstname'] ?? '').' '.
                $text($customer['lastname'] ?? $billing['lastname'] ?? '')),
            'company' => $text($billing['company'] ?? ''),
            'email' => $text($customer['email'] ?? ''),
            'phone' => $text($billing['phone'] ?? ''),
            'street' => $text($billing['street'] ?? ''),
            'postcode' => $text($billing['zipcode'] ?? ''),
            'city' => $text($billing['city'] ?? ''),
            'country' => $text($country['name'] ?? $country['iso'] ?? ''),
        ];
    }
}
