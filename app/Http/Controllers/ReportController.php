<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Models\Order;
use App\Reports\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(ReportRequest $request, ReportService $reports)
    {
        $filters = $request->filters();

        return view('reports.index', [
            'filters' => $filters,
            'report' => $reports->summary($filters),
            'regions' => Order::REGIONS,
            'categories' => Order::CATEGORIES,
        ]);
    }

    public function csv(ReportRequest $request): StreamedResponse
    {
        $filters = $request->filters();

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'ordered_at', 'customer', 'region', 'category', 'status', 'amount']);

            Order::filter($filters)->orderBy('id')->chunkById(1000, function ($orders) use ($out) {
                foreach ($orders as $order) {
                    fputcsv($out, [
                        $order->id,
                        $order->ordered_at->toDateTimeString(),
                        $order->customer_name,
                        $order->region,
                        $order->category,
                        $order->status,
                        number_format($order->amount / 100, 2, '.', ''),
                    ]);
                }
            });

            fclose($out);
        }, "orders-{$filters['from']}-to-{$filters['to']}.csv", ['Content-Type' => 'text/csv']);
    }

    public function pdf(ReportRequest $request, ReportService $reports)
    {
        $filters = $request->filters();

        return Pdf::loadView('reports.pdf', ['filters' => $filters, 'report' => $reports->summary($filters)])
            ->download("sales-report-{$filters['from']}-to-{$filters['to']}.pdf");
    }
}
