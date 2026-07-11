<?php

namespace App\Actions\Cms\Reports;

use App\Enums\PaymentStatusEnum;
use App\Models\Order\Order;
use Illuminate\Support\Carbon;

class GetSalesReportAction
{
    /**
     * Handle the action.
     *
     * @return array{period: string, year: int, month: int|null, available_years: array<int, int>, summary: array, rows: array}
     */
    public function handle(string $period, ?int $year = null, ?int $month = null): array
    {
        $period = in_array($period, ['daily', 'monthly', 'yearly'], true) ? $period : 'daily';
        $year = $year ?: (int) now()->year;
        $month = $month ?: (int) now()->month;

        $rows = $this->query($period, $year, $month)->get();

        $rows = match ($period) {
            'daily' => $this->fillDailyGaps($rows, $year, $month),
            'monthly' => $this->fillMonthlyGaps($rows, $year),
            default => $rows->map(fn ($row) => $this->rowOrZero($row, (string) $row->period_label))->values()->all(),
        };

        return [
            'period' => $period,
            'year' => $year,
            'month' => $month,
            'available_years' => $this->availableYears(),
            'summary' => [
                'transactions' => array_sum(array_column($rows, 'transactions')),
                'revenue' => array_sum(array_column($rows, 'revenue')),
                'estimated_profit' => array_sum(array_column($rows, 'estimated_profit')),
                'average_order_value' => $this->average($rows),
            ],
            'rows' => $rows,
        ];
    }

    /**
     * Base query: real, paid orders (excludes demo/seed orders and anything never actually paid).
     */
    protected function baseQuery()
    {
        return Order::query()
            ->join('payments', function ($join) {
                $join->on('payments.payable_id', '=', 'orders.id')
                    ->where('payments.payable_type', Order::class);
            })
            ->leftJoin('p_p_o_b_products', 'p_p_o_b_products.id', '=', 'orders.p_p_o_b_product_id')
            ->where('orders.payment_status', PaymentStatusEnum::SETTLEMENT->value)
            ->whereNotNull('payments.paid_at')
            ->where('orders.reference', 'not like', 'TRX-DEMO-%');
    }

    protected function query(string $period, int $year, int $month)
    {
        $query = $this->baseQuery();

        $periodExpr = match ($period) {
            'daily' => 'DATE(payments.paid_at)',
            'monthly' => "DATE_FORMAT(payments.paid_at, '%Y-%m')",
            'yearly' => 'YEAR(payments.paid_at)',
        };

        if ($period === 'daily') {
            $query->whereYear('payments.paid_at', $year)->whereMonth('payments.paid_at', $month);
        } elseif ($period === 'monthly') {
            $query->whereYear('payments.paid_at', $year);
        }

        return $query
            ->selectRaw("{$periodExpr} as period_label")
            ->selectRaw('COUNT(orders.id) as transactions')
            ->selectRaw('SUM(orders.total_amount) as revenue')
            ->selectRaw('SUM(orders.amount - orders.discount_amount - COALESCE(p_p_o_b_products.buy_price, 0)) as estimated_profit')
            ->groupBy('period_label')
            ->orderBy('period_label');
    }

    /**
     * Fill in zero-transaction days so the chart/table shows the full month, not just days with sales.
     */
    protected function fillDailyGaps($rows, int $year, int $month): array
    {
        $byLabel = $rows->keyBy('period_label');
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        $result = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $label = Carbon::create($year, $month, $day)->format('Y-m-d');
            $result[] = $this->rowOrZero($byLabel->get($label), $label);
        }

        return $result;
    }

    /**
     * Fill in zero-transaction months so the chart/table shows Jan-Dec, not just months with sales.
     */
    protected function fillMonthlyGaps($rows, int $year): array
    {
        $byLabel = $rows->keyBy('period_label');

        $result = [];
        for ($month = 1; $month <= 12; $month++) {
            $label = sprintf('%d-%02d', $year, $month);
            $result[] = $this->rowOrZero($byLabel->get($label), $label);
        }

        return $result;
    }

    protected function rowOrZero(?object $row, string $label): array
    {
        return [
            'period_label' => $label,
            'transactions' => (int) ($row->transactions ?? 0),
            'revenue' => (int) ($row->revenue ?? 0),
            'estimated_profit' => (int) ($row->estimated_profit ?? 0),
        ];
    }

    protected function average(array $rows): float
    {
        $transactions = array_sum(array_column($rows, 'transactions'));
        $revenue = array_sum(array_column($rows, 'revenue'));

        return $transactions > 0 ? round($revenue / $transactions, 2) : 0;
    }

    /**
     * @return array<int, int>
     */
    protected function availableYears(): array
    {
        return $this->baseQuery()
            ->selectRaw('DISTINCT YEAR(payments.paid_at) as y')
            ->orderByDesc('y')
            ->pluck('y')
            ->map(fn ($y) => (int) $y)
            ->values()
            ->all();
    }
}
