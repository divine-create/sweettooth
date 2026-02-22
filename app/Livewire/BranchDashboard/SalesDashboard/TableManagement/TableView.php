<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\TableManagement;

use App\Helpers\Settings;
use App\Models\Table;
use App\Services\CurrencyFormattingService;
use Livewire\Component;
use Livewire\Attributes\{Reactive, On};

class TableView extends Component
{
    #[Reactive]
    public Table $table;

    public ?int $selectedTableId = null;

    public function selectTable(): void
    {
        $this->dispatch('table-selected', tableId: $this->table->id);
        $this->selectedTableId = $this->table->id;
    }

    public function getStatusColorClass(): string
    {
        return match($this->table->status) {
            'available' => 'bg-green-100 border-green-300 hover:bg-green-200',
            'occupied' => 'bg-red-100 border-red-300 hover:bg-red-200',
            'reserved' => 'bg-yellow-100 border-yellow-300 hover:bg-yellow-200',
            default => 'bg-gray-100 border-gray-300',
        };
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->table->status) {
            'available' => 'bg-green-500',
            'occupied' => 'bg-red-500',
            'reserved' => 'bg-yellow-500',
            default => 'bg-gray-500',
        };
    }

    /**
     * Format currency
     */
    protected function formatCurrency(float $amount): string
    {
        $service = new CurrencyFormattingService();
        return $service->format($amount);
    }

    public function render()
    {
        return view('livewire.branch-dashboard.sales-dashboard.table-management.table-view');
    }
}
