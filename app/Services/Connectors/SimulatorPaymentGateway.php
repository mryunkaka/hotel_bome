<?php

namespace App\Services\Connectors;

use App\Contracts\Connectors\PaymentGatewayAdapter;
use App\Models\Property;
use App\Services\Sync\OutboxService;

/**
 * SimulatorPaymentGateway provides testing and outage-safe local fallback.
 * When WAN is down, requests are queued in the local outbox without crashing core PMS.
 */
class SimulatorPaymentGateway implements PaymentGatewayAdapter
{
    public function __construct(
        private readonly bool $isWanDown = false,
        private readonly ?OutboxService $outboxService = null,
    ) {}

    public function charge(Property $property, int $amount, string $idempotencyKey, array $details = []): array
    {
        if ($this->isWanDown) {
            // Outage-safe: queue to transactional outbox if service available
            $outboxId = null;
            if ($this->outboxService !== null) {
                $outboxRecord = $this->outboxService->enqueue(
                    $property,
                    'payment.charge_requested',
                    [
                        'amount' => $amount,
                        'idempotency_key' => $idempotencyKey,
                        'method' => $details['method'] ?? 'card',
                    ]
                );
                $outboxId = $outboxRecord->id;
            }

            return [
                'status' => 'offline_queued',
                'outbox_id' => $outboxId,
                'message' => 'WAN connection unavailable. Charge request queued safely in local outbox.',
            ];
        }

        $txRef = 'SIM-TX-'.uniqid();

        return [
            'status' => 'succeeded',
            'transaction_ref' => $txRef,
            'amount' => $amount,
            'idempotency_key' => $idempotencyKey,
        ];
    }

    public function refund(Property $property, string $transactionRef, int $amount, string $reason): array
    {
        if ($this->isWanDown) {
            return [
                'status' => 'offline_queued',
                'message' => 'WAN unavailable. Refund queued.',
            ];
        }

        return [
            'status' => 'succeeded',
            'refund_ref' => 'SIM-REF-'.uniqid(),
            'transaction_ref' => $transactionRef,
            'amount' => $amount,
            'reason' => $reason,
        ];
    }
}
