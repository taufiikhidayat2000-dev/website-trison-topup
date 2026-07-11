<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import SalesChart from '@/components/reports/SalesChart.vue';
import SalesStatsCards, {
    SalesSummary,
} from '@/components/reports/SalesStatsCards.vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFilter } from '@/composables/useFilter';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { Download } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface SalesRow {
    period_label: string;
    transactions: number;
    revenue: number;
    estimated_profit: number;
}

const props = defineProps<{
    period: 'daily' | 'monthly' | 'yearly';
    year: number;
    month: number;
    available_years: number[];
    summary: SalesSummary;
    rows: SalesRow[];
}>();

const title = 'Laporan Penjualan';
const description =
    'Laporan penjualan harian, bulanan, dan tahunan dari seluruh transaksi yang sudah dibayar.';

const breadcrumbItems: BreadcrumbItem[] = [{ title: title, href: '#' }];

const { updateParams } = useFilter();

const periodTabs = [
    { value: 'daily', label: 'Harian' },
    { value: 'monthly', label: 'Bulanan' },
    { value: 'yearly', label: 'Tahunan' },
];

const selectedYear = ref(props.year);
const selectedMonth = ref(props.month);

const monthNames = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

const switchPeriod = (period: string) => {
    updateParams({ period, year: selectedYear.value, month: selectedMonth.value });
};

const changeYear = (year: number) => {
    selectedYear.value = year;
    updateParams({ period: props.period, year, month: selectedMonth.value });
};

const changeMonth = (month: number) => {
    selectedMonth.value = month;
    updateParams({ period: props.period, year: selectedYear.value, month });
};

const yearOptions = computed(() => {
    const years = new Set(props.available_years);
    years.add(new Date().getFullYear());
    return Array.from(years).sort((a, b) => b - a);
});

const labelFormatter = (label: string): string => {
    if (props.period === 'daily') return dayjs(label).locale('id').format('DD MMM');
    if (props.period === 'monthly') return dayjs(label + '-01').locale('id').format('MMM YYYY');
    return label;
};

const exportUrl = computed(() => {
    const params = new URLSearchParams({
        period: props.period,
        year: String(selectedYear.value),
        month: String(selectedMonth.value),
    });
    return `/cms/reports/sales/export?${params.toString()}`;
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="title" />
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <Heading :title="title" :description="description" />
                <a :href="exportUrl">
                    <Button variant="outline">
                        <Download class="h-4 w-4" />
                        Export CSV
                    </Button>
                </a>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    v-for="tab in periodTabs"
                    :key="tab.value"
                    type="button"
                    class="rounded-lg border px-4 py-2 text-sm font-semibold transition-colors"
                    :class="
                        period === tab.value
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border/50 text-muted-foreground hover:border-primary/50'
                    "
                    @click="switchPeriod(tab.value)"
                >
                    {{ tab.label }}
                </button>

                <div v-if="period === 'daily'" class="ml-2 flex items-center gap-2">
                    <Select
                        :model-value="selectedMonth"
                        @update:model-value="(v) => changeMonth(Number(v))"
                    >
                        <SelectTrigger class="w-40">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="(name, i) in monthNames"
                                :key="i"
                                :value="i + 1"
                            >
                                {{ name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select
                        :model-value="selectedYear"
                        @update:model-value="(v) => changeYear(Number(v))"
                    >
                        <SelectTrigger class="w-28">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="y in yearOptions"
                                :key="y"
                                :value="y"
                            >
                                {{ y }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div v-else-if="period === 'monthly'" class="ml-2">
                    <Select
                        :model-value="selectedYear"
                        @update:model-value="(v) => changeYear(Number(v))"
                    >
                        <SelectTrigger class="w-28">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="y in yearOptions"
                                :key="y"
                                :value="y"
                            >
                                {{ y }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <SalesStatsCards :summary="summary" />

            <SalesChart
                :title="`Tren Omzet (${periodTabs.find((t) => t.value === period)?.label})`"
                :rows="rows"
                :label-formatter="labelFormatter"
            />

            <div class="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Periode</TableHead>
                            <TableHead>Jumlah Transaksi</TableHead>
                            <TableHead>Omzet</TableHead>
                            <TableHead>Estimasi Profit</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="rows.length === 0">
                            <TableCell
                                colspan="4"
                                class="py-8 text-center text-muted-foreground"
                            >
                                Belum ada transaksi pada periode ini.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="row in rows" :key="row.period_label">
                            <TableCell>{{ labelFormatter(row.period_label) }}</TableCell>
                            <TableCell>{{ row.transactions }}</TableCell>
                            <TableCell>{{ formatCurrency(row.revenue) }}</TableCell>
                            <TableCell>{{ formatCurrency(row.estimated_profit) }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppLayout>
</template>
