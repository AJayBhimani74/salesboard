<?php

namespace App\Reports;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportService
{
    private const VERSION_KEY = 'reports:version';

    private const TTL_MINUTES = 10;

    /** Called whenever an order changes: old cached reports are no longer used. */
    public static function forgetCached(): void
    {
        Cache::forever(self::VERSION_KEY, (int) Cache::get(self::VERSION_KEY, 1) + 1);
    }

    /** Fills in default dates and drops empty filters. */
    public static function normalise(array $input): array
    {
        $to = ! empty($input['to']) ? Carbon::parse($input['to']) : now();
        $from = ! empty($input['from']) ? Carbon::parse($input['from']) : $to->copy()->subMonths(11)->startOfMonth();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'region' => $input['region'] ?? null ?: null,
            'category' => $input['category'] ?? null ?: null,
        ];
    }

    public function summary(array $filters): array
    {
        $key = 'reports:'.Cache::get(self::VERSION_KEY, 1).':'.md5(json_encode($filters));

        return Cache::remember($key, now()->addMinutes(self::TTL_MINUTES), function () use ($filters) {
            return $this->build($filters) + ['generated_at' => now()->toDateTimeString()];
        });
    }

    private function build(array $filters): array
    {
        $totals = Order::filter($filters)
            ->selectRaw("count(*) as orders, coalesce(sum(case when status = 'completed' then amount end), 0) as revenue, coalesce(sum(case when status = 'cancelled' then 1 else 0 end), 0) as cancelled")
            ->first();

        $completed = $totals->orders - $totals->cancelled;

        return [
            'orders' => (int) $totals->orders,
            'revenue' => (int) $totals->revenue,
            'average_order' => $completed > 0 ? intdiv((int) $totals->revenue, $completed) : 0,
            'cancelled_rate' => $totals->orders > 0 ? round($totals->cancelled / $totals->orders * 100, 1) : 0.0,
            'monthly' => $this->monthly($filters),
            'by_category' => $this->revenueBy('category', $filters),
            'by_region' => $this->revenueBy('region', $filters),
        ];
    }

    /** @return array<string, int> 'YYYY-MM' => revenue in paise, with empty months filled in */
    private function monthly(array $filters): array
    {
        $month = DB::getDriverName() === 'sqlite' ? "strftime('%Y-%m', ordered_at)" : "date_format(ordered_at, '%Y-%m')";

        $rows = Order::filter($filters)
            ->where('status', 'completed')
            ->selectRaw("$month as month, sum(amount) as revenue")
            ->groupBy('month')
            ->pluck('revenue', 'month');

        $result = [];
        $cursor = Carbon::parse($filters['from'])->startOfMonth();
        $end = Carbon::parse($filters['to'])->startOfMonth();

        while ($cursor <= $end) {
            $result[$cursor->format('Y-m')] = (int) ($rows[$cursor->format('Y-m')] ?? 0);
            $cursor->addMonth();
        }

        return $result;
    }

    /** @return array<string, int> */
    private function revenueBy(string $column, array $filters): array
    {
        return Order::filter($filters)
            ->where('status', 'completed')
            ->selectRaw("$column as label, sum(amount) as revenue")
            ->groupBy($column)
            ->orderByDesc('revenue')
            ->pluck('revenue', 'label')
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}
