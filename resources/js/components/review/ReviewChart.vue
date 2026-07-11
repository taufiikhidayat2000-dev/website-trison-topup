<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import dayjs from 'dayjs';
import { computed } from 'vue';

const props = defineProps<{
    chart: { date: string; count: number }[];
}>();

const max = computed(() =>
    Math.max(1, ...props.chart.map((point) => point.count)),
);

const barWidth = computed(() =>
    props.chart.length > 0 ? 100 / props.chart.length : 0,
);
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="text-sm font-medium">
                Review 30 Hari Terakhir
            </CardTitle>
        </CardHeader>
        <CardContent>
            <div class="flex h-32 items-end gap-[2px]">
                <div
                    v-for="point in chart"
                    :key="point.date"
                    class="group relative flex-1"
                    :style="{ width: `${barWidth}%` }"
                >
                    <div
                        class="mx-auto w-full rounded-t bg-primary/70 transition-all group-hover:bg-primary"
                        :style="{
                            height: `${Math.max(4, (point.count / max) * 100)}px`,
                        }"
                    />
                    <div
                        class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 -translate-x-1/2 rounded bg-foreground px-2 py-1 text-xs whitespace-nowrap text-background opacity-0 transition-opacity group-hover:opacity-100"
                    >
                        {{ dayjs(point.date).format('DD MMM') }}:
                        {{ point.count }}
                    </div>
                </div>
            </div>
            <div
                class="mt-2 flex justify-between text-xs text-muted-foreground"
            >
                <span>{{ dayjs(chart[0]?.date).format('DD MMM') }}</span>
                <span>{{
                    dayjs(chart[chart.length - 1]?.date).format('DD MMM')
                }}</span>
            </div>
        </CardContent>
    </Card>
</template>
