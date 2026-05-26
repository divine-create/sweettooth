<?php

namespace App\Livewire\BranchDashboard\Accounting\Simple;

use App\Models\AccountingEntry;
use App\Models\AccountingPeriod;
use App\Models\BankAccount;
use App\Models\GlAccount;
use App\Models\GlEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app.branch-dashboard')]
class ExpenseImports extends Component
{
    use WithFileUploads;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public $file;
    public ?string $raw = null;
    public bool $hasHeaders = true;

    public bool $showPreview = false;
    public array $previewRows = [];
    public array $categories = [];
    public array $categoryKeys = [];
    public array $categoryMap = [];
    public array $categoryNewName = [];

    public ?int $defaultBankAccountId = null;
    public ?int $defaultBankGlAccountId = null;

    public function rules(): array
    {
        return [
            'file' => ['nullable', 'file', 'max:5120'],
            'raw' => ['nullable', 'string'],
            'defaultBankAccountId' => ['nullable', 'exists:bank_accounts,id'],
            'defaultBankGlAccountId' => ['nullable', 'exists:gl_accounts,id'],
        ];
    }

    public function analyze(): void
    {
        $this->validate();
        $content = $this->getContent();

        if (! $content) {
            session()->flash('error', 'Upload a file or paste data.');
            return;
        }

        [$headers, $rows] = $this->parseContent($content);
        if (empty($rows)) {
            session()->flash('error', 'No rows detected.');
            return;
        }

        $this->categories = $this->detectCategories($headers);
        $this->categoryKeys = $this->buildCategoryKeys($this->categories);
        $this->previewRows = $this->buildPreview($rows, $headers);
        $this->autoMapCategories();
        $this->showPreview = true;
    }

    public function import(): void
    {
        $this->validate();
        $content = $this->getContent();

        if (! $content) {
            session()->flash('error', 'Upload a file or paste data.');
            return;
        }

        [$headers, $rows] = $this->parseContent($content);
        if (empty($rows)) {
            session()->flash('error', 'No rows detected.');
            return;
        }

        $branchId = $this->b_id ?: request()->query('b_id') ?: current_branch_id();
        if (! $branchId) {
            session()->flash('error', 'Branch context is required.');
            return;
        }

        $period = AccountingPeriod::current()->first();
        if (! $period) {
            session()->flash('error', 'No open accounting period.');
            return;
        }

        $this->categories = $this->detectCategories($headers);
        $this->categoryKeys = $this->buildCategoryKeys($this->categories);

        $missing = $this->missingCategoryMappings();
        if (! empty($missing)) {
            session()->flash('error', 'Missing GL mappings for: ' . implode(', ', $missing));
            return;
        }

        $count = 0;
        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $row = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $row);
                $mapped = $this->mapWideRow($row, $headers);

                foreach ($mapped as $entry) {
                    $bankAccount = $this->resolveBankAccount($entry['bank']);
                    $bankGl = $this->resolveBankGl($bankAccount);

                    $debitGlId = $this->resolveCategoryGlId($entry['category']);

                    $accountingEntry = AccountingEntry::create([
                        'branch_id' => $branchId,
                        'accounting_period_id' => $period->id,
                        'entry_type' => 'expense',
                        'entry_date' => $entry['date'],
                        'description' => $entry['particulars'],
                        'reference' => null,
                        'amount' => $entry['amount'],
                        'debit_gl_account_id' => $debitGlId,
                        'credit_gl_account_id' => $bankGl->id,
                        'bank_account_id' => $bankAccount?->id,
                        'source' => $entry['source'],
                        'notes' => $entry['category'],
                        'created_by_id' => auth()->id(),
                    ]);

                    GlEntry::create([
                        'gl_account_id' => $debitGlId,
                        'accounting_period_id' => $period->id,
                        'entry_type' => 'expense',
                        'reference_type' => AccountingEntry::class,
                        'reference_id' => $accountingEntry->id,
                        'reference_number' => null,
                        'description' => $entry['particulars'],
                        'debit' => $entry['amount'],
                        'credit' => 0,
                        'entry_date' => $entry['date'],
                        'status' => 'draft',
                        'entered_by_id' => auth()->id(),
                        'branch_id' => $branchId,
                    ])->post(auth()->id());

                    GlEntry::create([
                        'gl_account_id' => $bankGl->id,
                        'accounting_period_id' => $period->id,
                        'entry_type' => 'expense',
                        'reference_type' => AccountingEntry::class,
                        'reference_id' => $accountingEntry->id,
                        'reference_number' => null,
                        'description' => $entry['particulars'],
                        'debit' => 0,
                        'credit' => $entry['amount'],
                        'entry_date' => $entry['date'],
                        'status' => 'draft',
                        'entered_by_id' => auth()->id(),
                        'branch_id' => $branchId,
                    ])->post(auth()->id());

                    $count++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('error', 'Import failed: ' . $e->getMessage());
            return;
        }

        session()->flash('message', "Imported {$count} entries.");
        $this->reset(['file', 'raw', 'showPreview', 'previewRows']);
    }

    private function getContent(): string
    {
        if ($this->file) {
            return (string) file_get_contents($this->file->getRealPath());
        }

        return (string) $this->raw;
    }

    private function parseContent(string $content): array
    {
        $content = trim($content);
        if ($content === '') {
            return [[], []];
        }

        $lines = preg_split("/\r\n|\n|\r/", $content);
        $rows = [];

        $delimiter = str_contains($content, "\t") ? "\t" : ",";

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $rows[] = str_getcsv($line, $delimiter, '"', "\\");
        }

        $headers = [];
        if ($this->hasHeaders && ! empty($rows)) {
            $headers = array_map(fn ($h) => trim((string) $h), array_shift($rows));
        }

        return [$headers, $rows];
    }

    private function detectCategories(array $headers): array
    {
        $start = 5;
        if (empty($headers)) {
            return [];
        }

        $categories = [];
        for ($i = $start; $i < count($headers); $i++) {
            $name = trim((string) $headers[$i]);
            if ($name !== '') {
                $categories[] = $name;
            }
        }

        if (empty($categories)) {
            $categories[] = 'Uncategorized';
        }

        return $categories;
    }

    private function buildPreview(array $rows, array $headers): array
    {
        $preview = [];
        foreach ($rows as $row) {
            $mapped = $this->mapWideRow($row, $headers);
            foreach ($mapped as $entry) {
                $preview[] = $entry;
                if (count($preview) >= 20) {
                    return $preview;
                }
            }
        }

        return $preview;
    }

    private function mapWideRow(array $row, array $headers): array
    {
        $data = [];

        if (count($row) < 4) {
            return $data;
        }

        $date = $this->parseDate($row[0] ?? '');
        $particulars = $row[1] ?? '';
        $source = $row[2] ?? '';
        $bank = $row[3] ?? '';
        $amount = $this->parseAmount($row[4] ?? '0');

        $hasCategory = false;
        if (! empty($headers)) {
            for ($i = 5; $i < count($row); $i++) {
                $val = $this->parseAmount($row[$i] ?? '');
                if ($val > 0) {
                    $category = $headers[$i] ?? "Category {$i}";
                    $data[] = [
                        'date' => $date,
                        'particulars' => $particulars,
                        'source' => $source,
                        'bank' => $bank,
                        'amount' => $val,
                        'category' => $category,
                    ];
                    $hasCategory = true;
                }
            }
        }

        if (! $hasCategory && $amount > 0) {
            $data[] = [
                'date' => $date,
                'particulars' => $particulars,
                'source' => $source,
                'bank' => $bank,
                'amount' => $amount,
                'category' => 'Uncategorized',
            ];
        }

        return $data;
    }

    private function parseAmount(string $value): float
    {
        $value = trim($value);
        if ($value === '' || $value === '-') {
            return 0.0;
        }
        $clean = str_replace([',', ' '], '', $value);
        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    private function parseDate(string $value): string
    {
        $value = trim($value);
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return Carbon::now()->format('Y-m-d');
        }
    }

    private function autoMapCategories(): void
    {
        foreach ($this->categories as $category) {
            $key = $this->keyForCategory($category);
            if (! isset($this->categoryMap[$key]) || ! $this->categoryMap[$key]) {
                $match = GlAccount::where('account_name', $category)->first();
                if ($match) {
                    $this->categoryMap[$key] = $match->id;
                }
            }
        }
    }

    private function missingCategoryMappings(): array
    {
        $missing = [];
        foreach ($this->categories as $category) {
            $key = $this->keyForCategory($category);
            $mapped = $this->categoryMap[$key] ?? null;
            $newName = trim((string) ($this->categoryNewName[$key] ?? ''));
            if (! $mapped && $newName === '') {
                $missing[] = $category;
            }
        }

        return $missing;
    }

    private function resolveCategoryGlId(string $category): int
    {
        $key = $this->keyForCategory($category);
        $mapped = $this->categoryMap[$key] ?? null;
        if ($mapped) {
            return (int) $mapped;
        }

        $newName = trim((string) ($this->categoryNewName[$key] ?? ''));
        if ($newName !== '') {
            $existing = GlAccount::where('account_name', $newName)->first();
            if ($existing) {
                $this->categoryMap[$key] = $existing->id;
                return (int) $existing->id;
            }

            $max = GlAccount::where('account_type', 'expense')->max('account_number');
            $next = $max ? (int) $max + 10 : 6000;

            $created = GlAccount::create([
                'account_number' => (string) $next,
                'account_name' => $newName,
                'account_type' => 'expense',
                'account_category' => null,
                'description' => null,
                'normal_balance' => 'debit',
                'is_header' => false,
                'is_active' => true,
                'allow_manual_entry' => true,
            ]);

            $this->categoryMap[$key] = $created->id;
            return (int) $created->id;
        }

        $fallback = GlAccount::where('account_name', $category)->first();
        if ($fallback) {
            return (int) $fallback->id;
        }

        throw new \RuntimeException("No GL account for category: {$category}");
    }

    private function buildCategoryKeys(array $categories): array
    {
        $keys = [];
        foreach ($categories as $category) {
            $keys[$this->keyForCategory($category)] = $category;
        }

        return $keys;
    }

    private function keyForCategory(string $category): string
    {
        return 'cat_' . md5($category);
    }

    private function resolveBankAccount(?string $source): ?BankAccount
    {
        $source = trim((string) $source);
        if ($source === '' || strcasecmp($source, 'all banks') === 0) {
            return $this->defaultBankAccountId ? BankAccount::find($this->defaultBankAccountId) : null;
        }

        if ($this->defaultBankAccountId) {
            $default = BankAccount::find($this->defaultBankAccountId);
            if ($default && str_contains(strtolower($source), strtolower($default->bank_name))) {
                return $default;
            }
        }

        $byName = BankAccount::where('bank_name', 'like', '%' . $source . '%')->first();
        if ($byName) {
            return $byName;
        }

        return BankAccount::where('account_number', 'like', '%' . $source . '%')->first();
    }

    private function resolveBankGl(?BankAccount $bankAccount): GlAccount
    {
        if ($bankAccount?->glAccount) {
            return $bankAccount->glAccount;
        }

        if ($this->defaultBankGlAccountId) {
            $selected = GlAccount::find($this->defaultBankGlAccountId);
            if ($selected) {
                return $selected;
            }
        }

        $fallback = GlAccount::where('account_number', '1050')->first();
        if (! $fallback) {
            throw new \RuntimeException('Bank GL account not found.');
        }

        return $fallback;
    }

    #[Computed]
    public function glAccounts()
    {
        return GlAccount::where('is_active', true)
            ->orderBy('account_number')
            ->get(['id', 'account_number', 'account_name', 'account_type']);
    }

    #[Computed]
    public function bankAccounts()
    {
        return BankAccount::query()
            ->with('glAccount')
            ->orderBy('bank_name')
            ->get(['id', 'bank_name', 'account_number', 'gl_account_id']);
    }

    public function render()
    {
        return view('livewire.branch-dashboard.accounting.simple.expense-imports');
    }
}
