<?php

namespace Modules\CustomerSystems\Services;

use RuntimeException;

class Shopware6Client
{
    const MAX_MATCHES = 5;
    private $request;

    public function __construct(callable $request)
    {
        $this->request = $request;
    }

    public function testConnection()
    {
        $this->search([ 'limit' => 1, 'includes' => ['customer' => ['id']], 'total-count-mode' => 0 ]);
        $orders = call_user_func($this->request, 'search/order', [
            'limit' => 1, 'total-count-mode' => 0,
            'associations' => ['orderCustomer' => ['associations' => ['customer' => new \stdClass()]], 'stateMachineState' => new \stdClass()],
            'includes' => ['order' => ['id', 'orderCustomer', 'stateMachineState'],
                'order_customer' => ['customerId', 'customer'], 'customer' => ['id'], 'state_machine_state' => ['technicalName']],
        ]);
        if (!is_array($orders) || !is_array($orders['data'] ?? null)) { throw new RuntimeException('invalid_response'); }
    }

    public function findByEmail($email)
    {
        $email = trim((string) $email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [];
        }
        $response = $this->search([
            'limit' => self::MAX_MATCHES + 1,
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
        if ((int) $response['total'] > self::MAX_MATCHES || count($response['data']) > self::MAX_MATCHES) {
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

    public function latestOrders($customerId, $email)
    {
        if (!is_string($customerId) || !preg_match('/^[a-f0-9]{32}$/i', $customerId)
            || !filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new \RuntimeException('invalid_response'); }
        $response = call_user_func($this->request, 'search/order', [
            'limit' => 5, 'total-count-mode' => 0,
            'sort' => [['field' => 'orderDateTime', 'order' => 'DESC'], ['field' => 'id', 'order' => 'DESC']],
            'filter' => [
                ['type' => 'equals', 'field' => 'orderCustomer.customerId', 'value' => $customerId],
                ['type' => 'equals', 'field' => 'orderCustomer.customer.email', 'value' => $email],
            ],
            'associations' => [
                'orderCustomer' => ['associations' => ['customer' => new \stdClass()]],
                'stateMachineState' => new \stdClass(),
            ],
            'includes' => [
                'order' => ['id', 'orderNumber', 'orderDateTime', 'orderCustomer', 'stateMachineState'],
                'order_customer' => ['customerId', 'customer'], 'customer' => ['id', 'email'],
                'state_machine_state' => ['name', 'technicalName', 'translated'],
            ],
        ]);
        if (!is_array($response) || !is_array($response['data'] ?? null)) { throw new \RuntimeException('invalid_response'); }
        $rows = [];
        foreach ($response['data'] as $row) {
            if (!is_array($row)) { throw new \RuntimeException('invalid_response'); }
            $owner = $row['orderCustomer'] ?? [];
            if (!is_array($owner) || !is_array($owner['customer'] ?? null)
                || !is_string($row['id'] ?? null) || !preg_match('/^[a-f0-9]{32}$/i', $row['id'])
                || !is_string($owner['customerId'] ?? null) || !is_string($owner['customer']['id'] ?? null)
                || !is_string($owner['customer']['email'] ?? null)
                || strcasecmp($owner['customerId'] ?? '', $customerId) !== 0
                || strcasecmp($owner['customer']['id'] ?? '', $customerId) !== 0
                || strcasecmp($owner['customer']['email'] ?? '', $email) !== 0) {
                throw new \RuntimeException('invalid_response');
            }
            $state = is_array($row['stateMachineState'] ?? null) ? $row['stateMachineState'] : [];
            $rows[] = ['id' => strtolower($row['id']), 'number' => $row['orderNumber'] ?? '',
                'ordered_at' => $row['orderDateTime'] ?? '',
                'status' => $state['translated']['name'] ?? $state['name'] ?? $state['technicalName'] ?? 'Unbekannt'];
        }
        return OrderSummary::latest($rows);
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
