<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Reports\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function order(array $attrs = []): Order
    {
        return Order::factory()->create(array_merge([
            'region' => 'North', 'category' => 'Toys', 'status' => 'completed',
            'amount' => 100000, 'ordered_at' => '2026-03-10 12:00:00',
        ], $attrs));
    }

    private function filters(array $extra = []): array
    {
        return ReportService::normalise(array_merge(['from' => '2026-01-01', 'to' => '2026-06-30'], $extra));
    }

    public function test_totals_and_averages_ignore_cancelled_orders_for_revenue(): void
    {
        $this->order(['amount' => 100000]);
        $this->order(['amount' => 300000]);
        $this->order(['amount' => 999999, 'status' => 'cancelled']);

        $report = (new ReportService)->summary($this->filters());

        $this->assertSame(3, $report['orders']);
        $this->assertSame(400000, $report['revenue']);
        $this->assertSame(200000, $report['average_order']);
        $this->assertSame(33.3, $report['cancelled_rate']);
    }

    public function test_empty_range_gives_zeroes_without_dividing_by_zero(): void
    {
        $report = (new ReportService)->summary($this->filters());

        $this->assertSame(0, $report['orders']);
        $this->assertSame(0, $report['average_order']);
        $this->assertSame(0.0, $report['cancelled_rate']);
        $this->assertSame([], $report['by_category']);
    }

    public function test_months_without_sales_are_filled_in_with_zero(): void
    {
        $this->order(['ordered_at' => '2026-01-15 10:00:00', 'amount' => 5000]);
        $this->order(['ordered_at' => '2026-03-15 10:00:00', 'amount' => 7000]);

        $monthly = (new ReportService)->summary($this->filters(['to' => '2026-04-30']))['monthly'];

        $this->assertSame(['2026-01' => 5000, '2026-02' => 0, '2026-03' => 7000, '2026-04' => 0], $monthly);
    }

    public function test_date_range_includes_both_end_days_and_excludes_outside(): void
    {
        $this->order(['ordered_at' => '2025-12-31 23:59:59']);
        $this->order(['ordered_at' => '2026-01-01 00:00:00']);
        $this->order(['ordered_at' => '2026-06-30 23:59:59']);
        $this->order(['ordered_at' => '2026-07-01 00:00:00']);

        $this->assertSame(2, (new ReportService)->summary($this->filters())['orders']);
    }

    public function test_region_and_category_filters(): void
    {
        $this->order(['region' => 'North', 'category' => 'Toys', 'amount' => 1000]);
        $this->order(['region' => 'South', 'category' => 'Toys', 'amount' => 2000]);
        $this->order(['region' => 'South', 'category' => 'Grocery', 'amount' => 4000]);

        $service = new ReportService;

        $this->assertSame(6000, $service->summary($this->filters(['region' => 'South']))['revenue']);
        $this->assertSame(4000, $service->summary($this->filters(['region' => 'South', 'category' => 'Grocery']))['revenue']);
        $this->assertSame(['South' => 6000, 'North' => 1000], $service->summary($this->filters(['category' => null]))['by_region']);
    }

    public function test_breakdowns_are_sorted_by_revenue(): void
    {
        $this->order(['category' => 'Toys', 'amount' => 1000]);
        $this->order(['category' => 'Grocery', 'amount' => 5000]);
        $this->order(['category' => 'Furniture', 'amount' => 3000]);

        $this->assertSame(
            ['Grocery' => 5000, 'Furniture' => 3000, 'Toys' => 1000],
            (new ReportService)->summary($this->filters())['by_category']
        );
    }

    public function test_second_request_with_same_filters_runs_no_queries(): void
    {
        $this->order();
        $service = new ReportService;
        $service->summary($this->filters());

        DB::enableQueryLog();
        $service->summary($this->filters());

        $this->assertCount(0, DB::getQueryLog());
    }

    public function test_changing_an_order_makes_the_cached_report_stale(): void
    {
        $order = $this->order(['amount' => 1000]);
        $service = new ReportService;
        $this->assertSame(1000, $service->summary($this->filters())['revenue']);

        $order->update(['amount' => 5000]);
        $this->assertSame(5000, $service->summary($this->filters())['revenue']);

        $this->order(['amount' => 1]);
        $this->assertSame(5001, $service->summary($this->filters())['revenue']);

        $order->delete();
        $this->assertSame(1, $service->summary($this->filters())['revenue']);
    }

    public function test_different_filters_do_not_share_a_cache_entry(): void
    {
        $this->order(['region' => 'North', 'amount' => 1000]);
        $this->order(['region' => 'South', 'amount' => 2000]);
        $service = new ReportService;

        $this->assertSame(1000, $service->summary($this->filters(['region' => 'North']))['revenue']);
        $this->assertSame(2000, $service->summary($this->filters(['region' => 'South']))['revenue']);
    }

    public function test_cache_survives_between_requests_until_it_expires(): void
    {
        $this->order(['amount' => 1000]);
        $service = new ReportService;
        $first = $service->summary($this->filters())['generated_at'];

        $this->travel(9)->minutes();
        $this->assertSame($first, $service->summary($this->filters())['generated_at']);

        $this->travel(2)->minutes();
        $this->assertNotSame($first, $service->summary($this->filters())['generated_at']);
    }

    public function test_defaults_are_the_last_twelve_months(): void
    {
        $this->travelTo('2026-10-23');

        $filters = ReportService::normalise([]);

        $this->assertSame('2025-11-01', $filters['from']);
        $this->assertSame('2026-10-23', $filters['to']);
    }
}
