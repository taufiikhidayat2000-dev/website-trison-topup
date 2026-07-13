<script setup lang="ts">
import { show } from '@/actions/App/Http/Controllers/Main/BrandController';
import { formatCurrency } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';
import { ShoppingBag } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    flashSaleProduct: {
        id: number;
        flash_price: number;
        discount_percent: number | null;
        flash_stock: number;
        sold: number;
        status: string;
        product: {
            name: string;
            sell_price: number;
            image: string | null;
            brand?: { slug: string; image?: string | null };
        };
    };
}>();

const isSoldOut = computed(() => props.flashSaleProduct.status === 'sold_out');
</script>

<template>
    <Link
        :href="
            flashSaleProduct.product.brand
                ? show({ brand: flashSaleProduct.product.brand.slug }).url
                : '#'
        "
        class="group flex flex-col gap-2 rounded-lg border border-border/50 bg-secondary p-3 shadow-sm transition-all duration-300 hover:scale-105 hover:border-primary/50 hover:shadow-lg"
        :class="{ 'opacity-60': isSoldOut }"
    >
        <div class="flex items-center gap-2">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-md bg-card"
            >
                <img
                    v-if="flashSaleProduct.product.brand?.image"
                    :src="flashSaleProduct.product.brand.image"
                    :alt="flashSaleProduct.product.name"
                    class="h-full w-full object-cover"
                />
                <ShoppingBag v-else class="h-5 w-5 text-muted-foreground opacity-30" />
            </div>
            <h3 class="line-clamp-2 text-xs font-semibold text-foreground">
                {{ flashSaleProduct.product.name }}
            </h3>
        </div>

        <div class="space-y-0.5">
            <span
                v-if="!isSoldOut"
                class="block text-[11px] text-muted-foreground line-through"
            >
                {{ formatCurrency(flashSaleProduct.product.sell_price) }}
            </span>
            <span class="block text-sm font-bold text-primary">
                {{ isSoldOut ? 'Habis' : formatCurrency(flashSaleProduct.flash_price) }}
            </span>
        </div>
    </Link>
</template>
