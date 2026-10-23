<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPagesTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): void
    {
        $this->actingAs(User::factory()->create());
    }

    public function test_pages_need_a_login(): void
    {
        foreach (['/', '/export/csv', '/export/pdf'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_dashboard_shows_the_numbers(): void
    {
        Order::factory()->create(['amount' => 1250000, 'ordered_at' => now()->subDays(3)]);
        $this->signIn();

        $this->get('/')->assertOk()->assertSee('Revenue')->assertSee('₹12,500');
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->signIn();

        $this->get('/?region=Mars')->assertSessionHasErrors('region');
        $this->get('/?from=2026-05-01&to=2026-01-01')->assertSessionHasErrors('to');
        $this->get('/?from=not-a-date')->assertSessionHasErrors('from');
    }

    public function test_csv_has_a_header_and_only_the_filtered_orders(): void
    {
        Order::factory()->create(['region' => 'North', 'amount' => 123456, 'customer_name' => 'Asha, K.', 'ordered_at' => '2026-03-05 10:00:00']);
        Order::factory()->create(['region' => 'South', 'ordered_at' => '2026-03-06 10:00:00']);
        Order::factory()->create(['region' => 'North', 'ordered_at' => '2025-01-06 10:00:00']);
        $this->signIn();

        $response = $this->get('/export/csv?from=2026-01-01&to=2026-12-31&region=North');

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $rows = array_map('str_getcsv', explode("\n", trim($response->streamedContent())));

        $this->assertSame(['id', 'ordered_at', 'customer', 'region', 'category', 'status', 'amount'], $rows[0]);
        $this->assertCount(2, $rows);
        $this->assertSame('Asha, K.', $rows[1][2]);
        $this->assertSame('1234.56', $rows[1][6]);
    }

    public function test_csv_streams_more_rows_than_one_chunk(): void
    {
        Order::factory()->count(1500)->create(['ordered_at' => '2026-03-05 10:00:00']);
        $this->signIn();

        $rows = explode("\n", trim($this->get('/export/csv?from=2026-01-01&to=2026-12-31')->streamedContent()));

        $this->assertCount(1501, $rows);
    }

    public function test_pdf_download(): void
    {
        Order::factory()->create(['ordered_at' => '2026-03-05 10:00:00']);
        $this->signIn();

        $response = $this->get('/export/pdf?from=2026-01-01&to=2026-12-31');

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
