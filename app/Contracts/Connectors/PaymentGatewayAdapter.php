<?php

namespace App\Contracts\Connectors;

use App\Models\Property;

interface PaymentGatewayAdapter
{
    /**
     * Charge a payment method online.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    public function charge(Property $property, int $amount, string $idempotencyKey, array $details = []): array;

    /**
     * Refund a previously captured payment.
     *
     * @return array<string, mixed>
     */
    public function refund(Property $property, string $transactionRef, int $amount, string $reason): array;
}
