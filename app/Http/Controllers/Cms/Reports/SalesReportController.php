<?php

namespace App\Http\Controllers\Cms\Reports;

use App\Actions\Cms\Reports\GetSalesReportAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReportController extends Controller
{
    /**
     * Display the sales report.
     */
    public function index(Request $request, GetSalesReportAction $action)
    {
        $report = $action->handle(
            period: $request->string('period', 'daily')->toString(),
            year: $request->integer('year') ?: null,
            month: $request->integer('month') ?: null,
        );

        return inertia('cms/reports/sales/Index', $report);
    }

    /**
     * Export the current report breakdown as CSV.
     */
    public function export(Request $request, GetSalesReportAction $action): StreamedResponse
    {
        $report = $action->handle(
            period: $request->string('period', 'daily')->toString(),
            year: $request->integer('year') ?: null,
            month: $request->integer('month') ?: null,
        );

        $filename = "laporan-penjualan-{$report['period']}-".now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Periode', 'Jumlah Transaksi', 'Omzet', 'Estimasi Profit']);

            foreach ($report['rows'] as $row) {
                fputcsv($handle, [
                    $row['period_label'],
                    $row['transactions'],
                    $row['revenue'],
                    $row['estimated_profit'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
