<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CalendarDays, MessageSquare, Star, TrendingUp } from 'lucide-vue-next';

export interface ReviewStats {
    total: number;
    average: number;
    today: number;
    this_month: number;
    distribution: Record<number, number>;
}

defineProps<{
    stats: ReviewStats;
}>();
</script>

<template>
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">
                    Total Review
                </CardTitle>
                <MessageSquare class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats.total }}</div>
            </CardContent>
        </Card>
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">
                    Average Rating
                </CardTitle>
                <Star class="h-4 w-4 fill-yellow-400 text-yellow-400" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats.average }}</div>
            </CardContent>
        </Card>
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium"> Hari Ini </CardTitle>
                <CalendarDays class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats.today }}</div>
            </CardContent>
        </Card>
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium"> Bulan Ini </CardTitle>
                <TrendingUp class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats.this_month }}</div>
            </CardContent>
        </Card>
    </div>

    <Card class="mt-4">
        <CardHeader>
            <CardTitle class="text-sm font-medium">
                Distribusi Rating
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-2">
            <div
                v-for="star in [5, 4, 3, 2, 1]"
                :key="star"
                class="flex items-center gap-3"
            >
                <span class="w-12 shrink-0 text-sm font-medium">
                    {{ star }} ★
                </span>
                <div class="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full bg-yellow-400 transition-all"
                        :style="{ width: `${stats.distribution[star] ?? 0}%` }"
                    />
                </div>
                <span class="w-12 shrink-0 text-right text-sm text-muted-foreground">
                    {{ stats.distribution[star] ?? 0 }}%
                </span>
            </div>
        </CardContent>
    </Card>
</template>
