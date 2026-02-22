<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPerformanceHistory extends Model
{
    use HasFactory;

    protected $table = 'supplier_performance_history';

    protected $fillable = [
        'supplier_id',
        'record_date',
        'on_time_delivery_percentage',
        'quality_rating',
        'price_competitiveness',
        'communication_rating',
        'lead_time_days',
        'overall_score',
        'notes',
    ];

    protected $casts = [
        'record_date' => 'date',
        'on_time_delivery_percentage' => 'decimal:2',
        'quality_rating' => 'decimal:1',
        'price_competitiveness' => 'decimal:1',
        'communication_rating' => 'decimal:1',
        'lead_time_days' => 'integer',
        'overall_score' => 'decimal:2',
    ];

    /**
     * Get the supplier this performance record belongs to.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Scope to get records by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('record_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get recent records (last N months).
     */
    public function scopeRecentMonths($query, $months = 3)
    {
        $startDate = now()->subMonths($months)->startOfMonth();
        return $query->where('record_date', '>=', $startDate);
    }

    /**
     * Calculate overall score from component ratings.
     */
    public static function calculateOverallScore($onTimePercentage, $qualityRating, $priceCompetitiveness, $communicationRating): float
    {
        // Weight the factors: 40% on-time, 30% quality, 20% price, 10% communication
        $weights = [
            'on_time' => 0.40,
            'quality' => 0.30,
            'price' => 0.20,
            'communication' => 0.10,
        ];

        $score = 0;

        if ($onTimePercentage !== null) {
            // Convert percentage to 0-5 scale
            $onTimeScore = ($onTimePercentage / 100) * 5;
            $score += $onTimeScore * $weights['on_time'];
        }

        if ($qualityRating !== null) {
            $score += $qualityRating * $weights['quality'];
        }

        if ($priceCompetitiveness !== null) {
            $score += $priceCompetitiveness * $weights['price'];
        }

        if ($communicationRating !== null) {
            $score += $communicationRating * $weights['communication'];
        }

        return round($score, 2);
    }

    /**
     * Get score rating (Excellent, Good, Fair, Poor).
     */
    public function getScoreRating(): string
    {
        if (!$this->overall_score) {
            return 'Not Rated';
        }

        if ($this->overall_score >= 4.5) {
            return 'Excellent';
        }
        if ($this->overall_score >= 3.5) {
            return 'Good';
        }
        if ($this->overall_score >= 2.5) {
            return 'Fair';
        }
        return 'Poor';
    }

    /**
     * Get score color for UI display.
     */
    public function getScoreColor(): string
    {
        $rating = $this->getScoreRating();

        return match ($rating) {
            'Excellent' => 'green',
            'Good' => 'blue',
            'Fair' => 'yellow',
            'Poor' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get individual metric status.
     */
    public function getMetricStatus($metric): string
    {
        $value = $this->{$metric};

        if ($value === null) {
            return 'not_available';
        }

        // For on_time_delivery_percentage (0-100)
        if ($metric === 'on_time_delivery_percentage') {
            if ($value >= 90) {
                return 'excellent';
            }
            if ($value >= 75) {
                return 'good';
            }
            if ($value >= 60) {
                return 'fair';
            }
            return 'poor';
        }

        // For rating metrics (0-5)
        if ($value >= 4.5) {
            return 'excellent';
        }
        if ($value >= 3.5) {
            return 'good';
        }
        if ($value >= 2.5) {
            return 'fair';
        }
        return 'poor';
    }

    /**
     * Update supplier with latest performance metrics.
     */
    public function updateSupplierMetrics(): void
    {
        $supplier = $this->supplier;

        $supplier->update([
            'on_time_delivery_percentage' => $this->on_time_delivery_percentage,
            'quality_rating' => $this->quality_rating,
            'avg_delivery_days' => $this->lead_time_days,
        ]);

        // Update credit rating based on overall score
        if ($this->overall_score) {
            $rating = match (true) {
                $this->overall_score >= 4.5 => 'excellent',
                $this->overall_score >= 3.5 => 'good',
                $this->overall_score >= 2.5 => 'fair',
                default => 'poor',
            };

            $supplier->update(['credit_rating' => $rating]);
        }
    }
}
