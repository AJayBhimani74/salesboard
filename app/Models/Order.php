<?php

namespace App\Models;

use App\Reports\ReportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public const REGIONS = ['North', 'South', 'East', 'West'];

    public const CATEGORIES = ['Electronics', 'Furniture', 'Clothing', 'Grocery', 'Toys'];

    protected $fillable = ['customer_name', 'region', 'category', 'status', 'amount', 'ordered_at'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'ordered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        $bump = fn () => ReportService::forgetCached();

        static::saved($bump);
        static::deleted($bump);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->whereBetween('ordered_at', [$filters['from'].' 00:00:00', $filters['to'].' 23:59:59'])
            ->when($filters['region'] ?? null, fn ($q, $region) => $q->where('region', $region))
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category', $category));
    }
}
