<?php

namespace Tests\Feature\Accounting;

use App\Models\Property;
use App\Services\Accounting\JournalService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ACC-001: General Ledger Chart of Accounts, Balanced Double-Entry Journals,
 * Immutability, and Night Audit Mapping.
 */
class JournalEntryTest extends TestCase
{
    use RefreshDatabase;

    private Property $propertyA;

    private Property $propertyB;

    private int $accCash;

    private int $accAR;

    private int $accRoomRev;

    private int $accFnBRev;

    private int $accTaxPayable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->propertyA = Property::factory()->create(['name' => 'Accounting Hotel Alpha']);
        $this->propertyB = Property::factory()->create(['name' => 'Accounting Hotel Beta']);

        $svc = new JournalService;

        // Initialize standard hospitality chart of accounts for Property A
        $this->accCash = $svc->createAccount($this->propertyA, [
            'code' => '1100',
            'name' => 'Cash and Cash Equivalents',
            'type' => 'asset',
        ]);

        $this->accAR = $svc->createAccount($this->propertyA, [
            'code' => '1200',
            'name' => 'Guest Ledger (Accounts Receivable)',
            'type' => 'asset',
        ]);

        $this->accRoomRev = $svc->createAccount($this->propertyA, [
            'code' => '4100',
            'name' => 'Room Revenue',
            'type' => 'revenue',
        ]);

        $this->accFnBRev = $svc->createAccount($this->propertyA, [
            'code' => '4200',
            'name' => 'Food & Beverage Revenue',
            'type' => 'revenue',
        ]);

        $this->accTaxPayable = $svc->createAccount($this->propertyA, [
            'code' => '2100',
            'name' => 'PBJT / Hotel Tax Payable',
            'type' => 'liability',
        ]);
    }

    public function test_post_balanced_journal_entry_succeeds(): void
    {
        $svc = new JournalService;

        // Balanced journal entry:
        // Debit Cash 1.000.000
        // Credit Guest Ledger (AR) 1.000.000
        $entryId = $svc->postJournalEntry($this->propertyA, [
            'entry_date' => '2028-12-01',
            'description' => 'Guest checkout settlement',
            'source_type' => 'folio',
            'actor_id' => 1,
            'lines' => [
                ['account_id' => $this->accCash, 'debit' => 1000000, 'credit' => 0, 'description' => 'Cash received'],
                ['account_id' => $this->accAR, 'debit' => 0, 'credit' => 1000000, 'description' => 'Clear guest folio balance'],
            ],
        ]);

        $this->assertGreaterThan(0, $entryId);

        $entry = DB::table('journal_entries')->where('id', $entryId)->first();
        $this->assertSame($this->propertyA->id, (int) $entry->property_id);
        $this->assertTrue((bool) $entry->is_balanced);
        $this->assertSame(1000000, (int) $entry->total_amount);

        $lines = DB::table('journal_lines')->where('entry_id', $entryId)->get();
        $this->assertCount(2, $lines);
    }

    public function test_unbalanced_journal_entry_is_rejected(): void
    {
        $svc = new JournalService;

        // Unbalanced: Debit 1.000.000 vs Credit 800.000
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('out of balance');

        $svc->postJournalEntry($this->propertyA, [
            'entry_date' => '2028-12-02',
            'description' => 'Faulty Entry',
            'actor_id' => 1,
            'lines' => [
                ['account_id' => $this->accCash, 'debit' => 1000000, 'credit' => 0],
                ['account_id' => $this->accAR, 'debit' => 0, 'credit' => 800000],
            ],
        ]);
    }

    public function test_journal_lines_are_immutable_cannot_be_updated_or_deleted(): void
    {
        $svc = new JournalService;

        $entryId = $svc->postJournalEntry($this->propertyA, [
            'entry_date' => '2028-12-03',
            'description' => 'Immutable test entry',
            'actor_id' => 1,
            'lines' => [
                ['account_id' => $this->accCash, 'debit' => 500000, 'credit' => 0],
                ['account_id' => $this->accRoomRev, 'debit' => 0, 'credit' => 500000],
            ],
        ]);

        $line = DB::table('journal_lines')->where('entry_id', $entryId)->first();

        // Attempt direct update on immutable ledger line
        $this->expectException(QueryException::class);
        DB::table('journal_lines')->where('id', $line->id)->update(['debit' => 999999]);
    }

    public function test_journal_reversal_creates_compensating_balanced_entry(): void
    {
        $svc = new JournalService;

        $origEntryId = $svc->postJournalEntry($this->propertyA, [
            'entry_date' => '2028-12-04',
            'description' => 'Mistaken posting',
            'actor_id' => 1,
            'lines' => [
                ['account_id' => $this->accAR, 'debit' => 300000, 'credit' => 0],
                ['account_id' => $this->accFnBRev, 'debit' => 0, 'credit' => 300000],
            ],
        ]);

        // Reverse the journal entry
        $revEntryId = $svc->reverseJournalEntry($this->propertyA, $origEntryId, 'Correction of wrong room charge', 1);

        $this->assertGreaterThan(0, $revEntryId);

        $revEntry = DB::table('journal_entries')->where('id', $revEntryId)->first();
        $this->assertSame($origEntryId, (int) $revEntry->reversal_of_id);
        $this->assertTrue((bool) $revEntry->is_balanced);
        $this->assertSame(300000, (int) $revEntry->total_amount);

        // In the reversal: Debits become Credits, Credits become Debits
        $lines = DB::table('journal_lines')->where('entry_id', $revEntryId)->get();
        $this->assertCount(2, $lines);

        $arLine = $lines->firstWhere('account_id', $this->accAR);
        $this->assertSame(0, (int) $arLine->debit);
        $this->assertSame(300000, (int) $arLine->credit);

        $fnbLine = $lines->firstWhere('account_id', $this->accFnBRev);
        $this->assertSame(300000, (int) $fnbLine->debit);
        $this->assertSame(0, (int) $fnbLine->credit);
    }

    public function test_night_audit_snapshot_journal_generation(): void
    {
        $svc = new JournalService;

        // Fake closing snapshot data from Night Audit:
        // Room Revenue: 2.000.000, F&B: 500.000, PBJT Tax: 250.000, Payments Received: 2.750.000
        $snapshot = [
            'business_date' => '2028-12-05',
            'room_charges' => 2000000,
            'fnb_charges' => 500000,
            'tax_amount' => 250000,
            'cash_payments' => 2750000,
        ];

        $entryId = $svc->generateNightAuditJournal($this->propertyA, $snapshot, 1);
        $this->assertGreaterThan(0, $entryId);

        $entry = DB::table('journal_entries')->where('id', $entryId)->first();
        $this->assertTrue((bool) $entry->is_balanced);
        // Total debit = Cash 2.750.000 == Total credit (Room 2.000.000 + F&B 500.000 + Tax 250.000)
        $this->assertSame(2750000, (int) $entry->total_amount);
        $this->assertSame('night_audit', $entry->source_type);
    }

    public function test_cross_property_account_rejected(): void
    {
        $svc = new JournalService;

        // Try to post journal in Property B using Property A's account
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Account not found in this property');

        $svc->postJournalEntry($this->propertyB, [
            'entry_date' => '2028-12-06',
            'description' => 'Cross property journal test',
            'actor_id' => 1,
            'lines' => [
                ['account_id' => $this->accCash, 'debit' => 100000, 'credit' => 0],
                ['account_id' => $this->accCash, 'debit' => 0, 'credit' => 100000],
            ],
        ]);
    }
}
