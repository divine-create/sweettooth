<?php

namespace Tests\Feature\Livewire\BranchDashboard\SalesDashboard;

use App\Livewire\BranchDashboard\SalesDashboard\StockMonitor;
use Livewire\Livewire;
use Tests\TestCase;

class StockMonitorTest extends TestCase
{
    public function test_renders_successfully()
    {
        Livewire::test(StockMonitor::class)
            ->assertStatus(200);
    }
}
