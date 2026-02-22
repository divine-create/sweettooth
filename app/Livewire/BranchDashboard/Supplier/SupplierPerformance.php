<?php

namespace App\Livewire\BranchDashboard\Supplier;

use App\Models\Supplier;
use App\Models\SupplierPerformanceHistory;
use Livewire\Component;

class SupplierPerformance extends Component
{
    public ?Supplier $supplier = null;
    public bool $showForm = false;
    public string $recordDate = '';
    public string $qualityScore = '';
    public string $deliveryScore = '';
    public string $priceCompetitivenessScore = '';
    public string $responseTimeScore = '';
    public string $complianceScore = '';
    public string $overallScore = '';
    public string $notes = '';

    protected function rules()
    {
        return [
            'recordDate' => 'required|date',
            'qualityScore' => 'required|numeric|min:0|max:100',
            'deliveryScore' => 'required|numeric|min:0|max:100',
            'priceCompetitivenessScore' => 'required|numeric|min:0|max:100',
            'responseTimeScore' => 'required|numeric|min:0|max:100',
            'complianceScore' => 'required|numeric|min:0|max:100',
            'overallScore' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function mount(Supplier $supplier): void
    {
        $this->supplier = $supplier;
        $this->recordDate = now()->toDateString();
    }

    public function toggleForm(): void
    {
        $this->showForm = !$this->showForm;
        if (!$this->showForm) {
            $this->resetForm();
        }
    }

    public function recordPerformance(): void
    {
        $validated = $this->validate();

        SupplierPerformanceHistory::create([
            'supplier_id' => $this->supplier->id,
            'record_date' => $validated['recordDate'],
            'quality_score' => $validated['qualityScore'],
            'delivery_score' => $validated['deliveryScore'],
            'price_competitiveness_score' => $validated['priceCompetitivenessScore'],
            'response_time_score' => $validated['responseTimeScore'],
            'compliance_score' => $validated['complianceScore'],
            'overall_score' => $validated['overallScore'],
            'notes' => $validated['notes'],
            'recorded_by' => auth()->id(),
        ]);

        // Update supplier quality rating with latest average
        $avgQuality = $this->supplier->performanceHistory()
            ->avg('quality_score');
        $this->supplier->update(['quality_rating' => $avgQuality]);

        session()->flash('success', 'Performance record added successfully');
        $this->showForm = false;
        $this->resetForm();
        $this->supplier->refresh();
        $this->dispatch('performanceUpdated');
    }

    public function deletePerformanceRecord($recordId): void
    {
        SupplierPerformanceHistory::find($recordId)?->delete();
        $this->supplier->refresh();
    }

    public function resetForm(): void
    {
        $this->reset([
            'qualityScore', 'deliveryScore', 'priceCompetitivenessScore',
            'responseTimeScore', 'complianceScore', 'overallScore', 'notes'
        ]);
        $this->recordDate = now()->toDateString();
    }

    public function getPerformanceTrendClass($currentScore, $previousScore = null): string
    {
        if ($previousScore === null) {
            return 'text-zinc-500';
        }

        if ($currentScore > $previousScore) {
            return 'text-green-600';
        } elseif ($currentScore < $previousScore) {
            return 'text-red-600';
        }

        return 'text-zinc-500';
    }

    public function getScoreColor($score): string
    {
        if ($score >= 80) {
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
        } elseif ($score >= 60) {
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
        }

        return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
    }

    public function render()
    {
        return view('livewire.branch-dashboard.supplier.supplier-performance', [
            'performanceHistory' => $this->supplier->performanceHistory()
                ->latest('record_date')
                ->get(),
            'latestPerformance' => $this->supplier->getLatestPerformance(),
        ]);
    }
}
