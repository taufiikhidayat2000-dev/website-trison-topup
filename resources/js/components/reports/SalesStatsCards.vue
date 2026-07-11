<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency } from '@/lib/utils';
import { ReceiptText, TrendingUp, Wallet } from 'lucide-vue-next';

export interface SalesSummary {
    transactions: number;
    revenue: number;
    estimated_profit: number;
    average_order_value: number;
}

defineProps<{
    summary: SalesSummary;
}>();
</script>

<template>
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">
                    Total Transaksi
                </CardTitle>
                <ReceiptText class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ summary.transactions }}</div>
            </CardContent>
        </Card>
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium"> Omzet </CardTitle>
                <Wallet class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-green-600">
                    {{ formatCurrency(summary.revenue) }}
                </div>
            </CardContent>
        </Card>
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">
                    Rata-rata / Transaksi
                </CardTitle>
                <TrendingUp class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">
                    {{ formatCurrency(summary.average_order_value) }}
                </div>
            </CardContent>
        </Card>
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">
                    Estimasi Profit
                </CardTitle>
                <Wallet class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-primary">
                    {{ formatCurrency(summary.estimated_profit) }}
                </div>
                <p class="text-xs text-muted-foreground">
                    Dihitung dari harga modal saat ini, bukan snapshot saat
                    transaksi
                </p>
            </CardContent>
        </Card>
    </div>
</template>
