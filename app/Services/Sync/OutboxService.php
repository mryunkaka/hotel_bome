<?php

namespace App\Services\Sync;

use App\Models\Property;
use App\Models\SyncOutbox;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * OutboxService implements the Transactional Outbox Pattern for WAN-safe sync.
 *
 * Implements SYNC-001 requirements:
 * - Local-first core operations commit events to outbox within the same DB transaction.
 * - Outbox events queue safely while WAN is down.
 * - Enforces D-007 guest PII exclusion: rejects synchronization of unauthorized
 *   personal identity data (phone, email, ID card, passport, NIK).
 * - Bounded retries with exponential backoff and dead-letter failure state.
 * - Multi-tenant isolation by property_id.
 */
class OutboxService
{
    private const PROHIBITED_PII_KEYS = [
        'email',
        'phone',
        'id_card',
        'passport',
        'nik',
        'address',
        'date_of_birth',
    ];

    /**
     * Enqueue an event in the outbox.
     *
     * @param  array<string, mixed>  $payload
     */
    public function enqueue(
        Property $property,
        string $topic,
        array $payload,
        ?string $eventUuid = null,
        int $maxAttempts = 5,
    ): SyncOutbox {
        // Enforce D-007 guest privacy protection: reject payloads with prohibited PII
        $this->guardPiiExclusion($payload);

        $uuid = $eventUuid ?? (string) Str::uuid();

        return SyncOutbox::create([
            'property_id' => $property->id,
            'event_uuid' => $uuid,
            'topic' => $topic,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'status' => 'queued',
            'attempts' => 0,
            'max_attempts' => $maxAttempts,
        ]);
    }

    /**
     * Fetch pending outbox events ready for dispatch.
     *
     * @return Collection<int, SyncOutbox>
     */
    public function fetchPendingEvents(Property $property, int $limit = 50): Collection
    {
        $now = now();

        return SyncOutbox::where('property_id', $property->id)
            ->where('status', 'queued')
            ->where(function ($query) use ($now): void {
                $query->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', $now);
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Mark an outbox event as successfully delivered to the remote endpoint.
     */
    public function markDelivered(int $outboxId, Property $property): void
    {
        DB::transaction(function () use ($outboxId, $property): void {
            $event = $this->guardOutbox($outboxId, $property);

            $event->update([
                'status' => 'delivered',
                'delivered_at' => now(),
                'last_error' => null,
            ]);
        });
    }

    /**
     * Mark an event delivery attempt as failed, applying bounded retry logic.
     */
    public function markFailed(int $outboxId, Property $property, string $errorMessage): void
    {
        DB::transaction(function () use ($outboxId, $property, $errorMessage): void {
            $event = $this->guardOutbox($outboxId, $property);

            $newAttempts = $event->attempts + 1;
            $maxAttempts = $event->max_attempts;

            if ($newAttempts >= $maxAttempts) {
                // Permanently dead-lettered
                $event->update([
                    'attempts' => $newAttempts,
                    'status' => 'failed',
                    'last_error' => $errorMessage,
                    'next_retry_at' => null,
                ]);
            } else {
                // Exponential backoff: 2^attempts minutes
                $backoffMinutes = 2 ** $newAttempts;
                $nextRetry = now()->addMinutes($backoffMinutes);

                $event->update([
                    'attempts' => $newAttempts,
                    'status' => 'queued',
                    'last_error' => $errorMessage,
                    'next_retry_at' => $nextRetry,
                ]);
            }
        });
    }

    /**
     * Manually retry a failed dead-lettered event.
     */
    public function retryFailed(Property $property, int $outboxId): void
    {
        DB::transaction(function () use ($property, $outboxId): void {
            $event = $this->guardOutbox($outboxId, $property);

            $event->update([
                'status' => 'queued',
                'attempts' => 0,
                'next_retry_at' => null,
                'last_error' => null,
            ]);
        });
    }

    private function guardOutbox(int $outboxId, Property $property): SyncOutbox
    {
        $event = SyncOutbox::where('id', $outboxId)
            ->where('property_id', $property->id)
            ->first();

        if ($event === null) {
            throw new \DomainException('Outbox event not found in this property.');
        }

        return $event;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function guardPiiExclusion(array $payload): void
    {
        foreach ($payload as $key => $value) {
            $lowerKey = strtolower((string) $key);
            foreach (self::PROHIBITED_PII_KEYS as $prohibited) {
                if (str_contains($lowerKey, $prohibited)) {
                    throw new \DomainException("Sync payload contains prohibited guest PII key: {$key}. D-007 guest privacy approval required.");
                }
            }

            if (is_array($value)) {
                $this->guardPiiExclusion($value);
            }
        }
    }
}
