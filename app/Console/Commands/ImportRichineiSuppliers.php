<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ReadsRichineiExport;
use App\Models\Supplier;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportRichineiSuppliers extends Command
{
    use ReadsRichineiExport;

    protected $signature = 'import:richinei-suppliers
        {--branch-id= : Branch UUID to assign suppliers to (defaults to first branch)}
        {--dry-run : Parse and report only; write nothing}';

    protected $description = 'Import suppliers from the Richinei master-data export (data-migration/out/suppliers.json)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $branchId = $this->resolveBranchId($this->option('branch-id'));

        $rows = $this->loadExport('suppliers');
        if (empty($rows)) {
            $this->warn('No suppliers to import.');

            return self::SUCCESS;
        }

        $this->info(count($rows).' source suppliers found.'.($dryRun ? '  [DRY RUN]' : ''));

        $created = $updated = $skipped = 0;

        foreach ($rows as $row) {
            $name = $this->cleanName($row['supplier_business_name'] ?? null)
                ?: $this->cleanName($row['name'] ?? null)
                ?: trim(($row['first_name'] ?? '').' '.($row['last_name'] ?? ''));

            if ($name === '') {
                $skipped++;

                continue;
            }

            // Idempotency: source contact_id is stable & unique. Prefix to avoid
            // colliding with Sweettooth's own SUP-* codes. Also de-dupe by name so
            // a supplier already entered manually isn't duplicated.
            $code = 'RICH-'.($row['contact_id'] ?? Str::slug($name));

            $existing = Supplier::query()
                ->where('code', $code)
                ->orWhereRaw('LOWER(name) = ?', [strtolower($name)])
                ->first();

            $address = trim(implode(', ', array_filter([
                $row['address_line_1'] ?? null,
                $row['address_line_2'] ?? null,
            ])));

            $payTerm = (int) ($row['pay_term_number'] ?? 0);
            $payTermDays = ($row['pay_term_type'] ?? null) === 'days' && $payTerm > 0 ? $payTerm : 30;

            $attrs = [
                'name' => $name,
                'email' => $row['email'] ?? null,
                'phone' => $row['mobile'] ?? ($row['landline'] ?? null),
                'address' => $address ?: null,
                'postal_code' => $row['zip_code'] ?? null,
                'tax_id' => $row['tax_number'] ?? null,
                'credit_limit' => $this->money($row['credit_limit'] ?? null),
                'payment_terms_days' => $payTermDays,
                'outstanding_balance' => $this->money($row['opening_balance'] ?? ($row['balance_due'] ?? null)),
                'status' => ($row['status'] ?? 'active') === 'active' ? 'active' : 'inactive',
                'branch_id' => $branchId,
                'notes' => 'Imported from Richinei ('.($row['contact_id'] ?? 'n/a').')',
            ];

            if ($existing) {
                // Fill-only: never clobber values already present in Sweettooth.
                $fill = [];
                foreach (['email', 'phone', 'address', 'tax_id', 'postal_code'] as $f) {
                    if (empty($existing->{$f}) && ! empty($attrs[$f])) {
                        $fill[$f] = $attrs[$f];
                    }
                }
                if ($fill && ! $dryRun) {
                    $existing->update($fill);
                }
                $fill ? $updated++ : $skipped++;

                continue;
            }

            if (! $dryRun) {
                Supplier::create(['code' => $code] + $attrs);
            }
            $created++;
        }

        $this->newLine();
        $this->info("Suppliers — created: {$created}, updated: {$updated}, skipped: {$skipped}");

        if ($dryRun) {
            $this->warn('DRY RUN: nothing was written.');
        }

        return self::SUCCESS;
    }
}
