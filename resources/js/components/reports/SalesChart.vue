<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency } from '@/lib/utils';
import { computed } from 'vue';

const props = defineProps<{
    title: string;
    rows: { period_label: string; revenue: number }[];
    labelFormatter: (label: string) => string;
}>();

const max = computed(() =>
    Math.max(1, ...props.rows.map((row) => row.revenue)),
);

const barWidth = computed(() =>
    props.rows.length > 0 ? 100 / props.rows.length : 0,
);
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="text-sm font-medium">{{ title }}</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="flex h-40 items-end gap-[2px]">
                <div
                    v-for="row in rows"
                    :key="row.period_label"
                    class="group relative flex-1"
                    :style="{ width: `${barWidth}%` }"
                >
                    <div
                        class="mx-auto w-full rounded-t bg-primary/70 transition-all group-hover:bg-primary"
                        :style="{
                            height: `${Math.max(2, (row.revenue / max) * 120)}px`,
                        }"
                    />
                    <div
                        class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 -translate-x-1/2 rounded bg-foreground px-2 py-1 text-xs whitespace-nowrap text-background opacity-0 transition-opacity group-hover:opacity-100"
                    >
                        {{ labelFormatter(row.period_label) }}:
                        {{ formatCurrency(row.revenue) }}
                    </div>
                </div>
            </div>
            <div
                v-if="rows.length > 0"
                class="mt-2 flex justify-between text-xs text-muted-foreground"
            >
                <span>{{ labelFormatter(rows[0].period_label) }}</span>
                <span>{{ labelFormatter(rows[rows.length - 1].period_label) }}</span>
            </div>
        </CardContent>
    </Card>
</template>
