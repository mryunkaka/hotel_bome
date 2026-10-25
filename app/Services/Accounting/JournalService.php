<?php

namespace App\Services\Accounting;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Property;
use Illuminate\Support\Facades\DB;

/**
 * JournalService manages double-entry general ledger postings,
 * chart of accounts, strict balance enforcement (Debits == Credits),
 * immutable ledger history, and Night Audit daily journal generation.
 *
 * Implements ACC-001 requirements:
 * - Debits must strictly equal Credits.
 * - Append-only ledger protected by SQLite triggers (no UPDATE or DELETE).
 * - Multi-tenant isolation by property_id.
 * - Reversal entries create compensating balanced journal records.
 */
class JournalService
{
    /**
     * Create an account in the property's Chart of Accounts.
     *
     * @param  array{code: string, name: string, type: string}  $data
     */
    public function createAccount(Property $property, array $data): int
    {
        return (int) ChartOfAccount::create([
            'property_id' => $property->id,
            'code' => $data['code'],
            'name' => $data['name'],
            'type' => $data['type'],
            'is_active' => true,
        ])->id;
    }

    /**
     * Post a balanced journal entry with lines.
     *
     * @param  array{
     *     entry_date: string,
     *     description: string,
     *     source_type?: string,
     *     source_id?: int|null,
     *     actor_id: int,
     *     lines: list<array{account_id: int, debit: int, credit: int, description?: string|null}>
     * }  $data
     */
    public function postJournalEntry(Property $property, array $data): int
    {
        return DB::transaction(function () use ($property, $data): int {
            $linesData = $data['lines'];
            if (empty($linesData)) {
                throw new \DomainException('Journal entry must contain at least one line.');
            }

            $totalDebit = 0;
            $totalCredit = 0;

            // Validate accounts belong to this property and calculate totals
            foreach ($linesData as $line) {
                $account = ChartOfAccount::where('id', $line['account_id'])
                    ->where('property_id', $property->id)
                    ->first();

                if ($account === null) {
                    throw new \DomainException("Account not found in this property (id: {$line['account_id']}).");
                }

                $totalDebit += (int) $line['debit'];
                $totalCredit += (int) $line['credit'];
            }

            // Invariant: Debits MUST equal Credits
            if ($totalDebit !== $totalCredit) {
                throw new \DomainException("Journal entry is out of balance: Debits ({$totalDebit}) != Credits ({$totalCredit}).");
            }

            if ($totalDebit <= 0) {
                throw new \DomainException('Journal entry total amount must be greater than zero.');
            }

            $entryNumber = sprintf('JE-%s-%s-%s', $property->id, date('Ymd'), uniqid());

            $entry = JournalEntry::create([
                'property_id' => $property->id,
                'entry_number' => $entryNumber,
                'entry_date' => $data['entry_date'],
                'description' => $data['description'],
                'source_type' => $data['source_type'] ?? 'manual',
                'source_id' => $data['source_id'] ?? null,
                'is_balanced' => true,
                'total_amount' => $totalDebit,
                'created_by' => $data['actor_id'],
            ]);

            foreach ($linesData as $line) {
                JournalLine::create([
                    'entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => (int) $line['debit'],
                    'credit' => (int) $line['credit'],
                    'description' => $line['description'] ?? null,
                ]);
            }

            return (int) $entry->id;
        });
    }

    /**
     * Create a balanced compensating reversal for a journal entry.
     */
    public function reverseJournalEntry(Property $property, int $origEntryId, string $reason, int $actorId): int
    {
        return DB::transaction(function () use ($property, $origEntryId, $reason, $actorId): int {
            $origEntry = JournalEntry::where('id', $origEntryId)
                ->where('property_id', $property->id)
                ->first();

            if ($origEntry === null) {
                throw new \DomainException('Original journal entry not found in this property.');
            }

            $origLines = JournalLine::where('entry_id', $origEntry->id)->get();

            // Reverse lines: Debits become Credits, Credits become Debits
            $revLines = [];
            foreach ($origLines as $l) {
                $revLines[] = [
                    'account_id' => (int) $l->account_id,
                    'debit' => (int) $l->credit,
                    'credit' => (int) $l->debit,
                    'description' => 'Reversal: '.$l->description,
                ];
            }

            $revEntryNumber = sprintf('REV-%s-%s', $origEntry->entry_number, uniqid());

            $revEntry = JournalEntry::create([
                'property_id' => $property->id,
                'entry_number' => $revEntryNumber,
                'entry_date' => date('Y-m-d'),
                'description' => "Reversal of {$origEntry->entry_number}: {$reason}",
                'source_type' => $origEntry->source_type,
                'source_id' => $origEntry->id,
                'is_balanced' => true,
                'total_amount' => $origEntry->total_amount,
                'reversal_of_id' => $origEntry->id,
                'reason' => $reason,
                'created_by' => $actorId,
            ]);

            foreach ($revLines as $line) {
                JournalLine::create([
                    'entry_id' => $revEntry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'description' => $line['description'],
                ]);
            }

            return (int) $revEntry->id;
        });
    }

    /**
     * Generate balanced daily journal entry from Night Audit closing snapshot.
     *
     * @param  array{
     *     business_date: string,
     *     room_charges?: int,
     *     fnb_charges?: int,
     *     tax_amount?: int,
     *     cash_payments?: int
     * }  $snapshot
     */
    public function generateNightAuditJournal(Property $property, array $snapshot, int $actorId): int
    {
        // Find property standard accounts
        $cashAcc = ChartOfAccount::where('property_id', $property->id)->where('code', '1100')->firstOrFail();
        $roomAcc = ChartOfAccount::where('property_id', $property->id)->where('code', '4100')->firstOrFail();
        $fnbAcc = ChartOfAccount::where('property_id', $property->id)->where('code', '4200')->firstOrFail();
        $taxAcc = ChartOfAccount::where('property_id', $property->id)->where('code', '2100')->firstOrFail();

        $cash = $snapshot['cash_payments'] ?? 0;
        $room = $snapshot['room_charges'] ?? 0;
        $fnb = $snapshot['fnb_charges'] ?? 0;
        $tax = $snapshot['tax_amount'] ?? 0;

        $lines = [];

        // Debit: Cash received
        if ($cash > 0) {
            $lines[] = [
                'account_id' => (int) $cashAcc->id,
                'debit' => $cash,
                'credit' => 0,
                'description' => 'Daily Cash Receipts',
            ];
        }

        // Credits: Revenues and Tax Liability
        if ($room > 0) {
            $lines[] = [
                'account_id' => (int) $roomAcc->id,
                'debit' => 0,
                'credit' => $room,
                'description' => 'Daily Room Revenue',
            ];
        }

        if ($fnb > 0) {
            $lines[] = [
                'account_id' => (int) $fnbAcc->id,
                'debit' => 0,
                'credit' => $fnb,
                'description' => 'Daily Food & Beverage Revenue',
            ];
        }

        if ($tax > 0) {
            $lines[] = [
                'account_id' => (int) $taxAcc->id,
                'debit' => 0,
                'credit' => $tax,
                'description' => 'Daily PBJT / Hotel Tax Accrual',
            ];
        }

        return $this->postJournalEntry($property, [
            'entry_date' => $snapshot['business_date'],
            'description' => "Night Audit Daily Revenue Journal - {$snapshot['business_date']}",
            'source_type' => 'night_audit',
            'actor_id' => $actorId,
            'lines' => $lines,
        ]);
    }
}
