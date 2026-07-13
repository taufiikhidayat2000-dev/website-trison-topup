<script setup lang="ts">
import FlashSaleProductCard from '@/components/FlashSaleProductCard.vue';
import { useCountdown } from '@/composables/useCountdown';
import { computed } from 'vue';

const props = defineProps<{
    flashSale: {
        title: string;
        subtitle: string | null;
        icon_type: string;
        icon_emoji: string | null;
        icon_image_url: string | null;
        end_time: string;
        products: any[];
    };
}>();

const { days, hours, minutes, seconds, pad } = useCountdown(
    computed(() => props.flashSale.end_time),
);
</script>

<template>
    <section
        class="mb-8 rounded-lg border border-border/50 bg-card p-6 shadow-sm"
    >
        <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span v-if="flashSale.icon_type === 'emoji'" class="text-3xl">
                    {{ flashSale.icon_emoji ?? '🔥' }}
                </span>
                <img
                    v-else-if="flashSale.icon_image_url"
                    :src="flashSale.icon_image_url"
                    class="h-8 w-8 object-contain"
                />
                <div>
                    <h2 class="text-xl font-bold text-foreground">
                        {{ flashSale.title }}
                    </h2>
                    <p v-if="flashSale.subtitle" class="text-sm text-muted-foreground">
                        {{ flashSale.subtitle }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 rounded-lg bg-secondary px-4 py-2">
                <span class="text-xs text-muted-foreground">Berakhir Dalam</span>
                <div class="flex items-center gap-1 font-mono text-sm font-bold text-primary">
                    <span v-if="days > 0">{{ pad(days) }}:</span>
                    <span>{{ pad(hours) }}:</span>
                    <span>{{ pad(minutes) }}:</span>
                    <span>{{ pad(seconds) }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            <FlashSaleProductCard
                v-for="fsp in flashSale.products"
                :key="fsp.id"
                :flash-sale-product="fsp"
            />
        </div>
    </section>
</template>
