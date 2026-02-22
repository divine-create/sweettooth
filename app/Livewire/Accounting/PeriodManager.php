                                                                                                                                                                                                                                                                                                                                                                          

namespace App\Livewire\Accounting;

use App\Models\AccountingPeriod;
use Livewire\Component;
use Livewire\Attributes\Computed;

class PeriodManager extends Component
{
    public string $filterStatus = '';
    public string $sortBy = 'period_start';

    #[Computed]
    public function periods()
    {
        return AccountingPeriod::query()
            ->when($this->filterStatus, function ($q) {
                return $q->where('status', $this->filterStatus);
            })
            ->orderBy($this->sortBy, 'desc')
            ->get();
    }

    public function closePeriod(AccountingPeriod $period)
    {
        if ($period->status !== 'open') {
            session()->flash('error', 'Only open periods can be closed');
            return;
        }

        try {
            $period->update(['status' => 'closed', 'closed_at' => now()]);
            session()->flash('success', "Period {$period->name} closed successfully");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function reopenPeriod(AccountingPeriod $period)
    {
        if ($period->status !== 'closed') {
            session()->flash('error', 'Only closed periods can be reopened');
            return;
        }

        try {
            $period->update(['status' => 'open', 'closed_at' => null]);
            session()->flash('success', "Period {$period->name} reopened successfully");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function lockPeriod(AccountingPeriod $period)
    {
        try {
            $period->update(['is_locked' => true]);
            session()->flash('success', "Period {$period->name} locked successfully");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function unlockPeriod(AccountingPeriod $period)
    {
        try {
            $period->update(['is_locked' => false]);
            session()->flash('success', "Period {$period->name} unlocked successfully");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.accounting.period-manager');
    }
}
