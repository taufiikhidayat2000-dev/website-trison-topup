<script setup lang="ts">
import { formatCurrency } from '@/lib/utils';
import { PPOBProductDataItem } from '@/types/cms/ppob';
import { ClockIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    products: PPOBProductDataItem[];
    selectedProduct: number | null;
}>();

const emit = defineEmits<{
    'update:selectedProduct': [value: number];
}>();

// Distinct product categories present among this brand's products, in order of first appearance
const categories = computed(() => {
    const seen = new Map<number, string>();
    for (const product of props.products) {
        if (
            product.product_category &&
            !seen.has(product.product_category.id)
        ) {
            seen.set(
                product.product_category.id,
                product.product_category.name,
            );
        }
    }
    return Array.from(seen, ([id, name]) => ({ id, name }));
});

// Active category filter (null = show all products)
const activeCategory = ref<number | null>(null);

const filteredProducts = computed(() => {
    if (activeCategory.value === null) {
        return props.products;
    }
    return props.products.filter(
        (product) =>
            product.p_p_o_b_product_category_id === activeCategory.value,
    );
});
</script>

<template>
    <section class="rounded-lg border border-border/50 bg-card p-6 shadow-sm">
        <h2
            class="mb-4 flex items-center gap-2 text-lg font-bold text-foreground"
        >
            <span
                class="flex h-7 w-7 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground"
                >2</span
            >
            Pilih Produk
        </h2>

        <div v-if="categories.length > 0" class="mb-4 flex flex-wrap gap-2">
            <button
                type="button"
                class="rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors"
                :class="
                    activeCategory === null
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-border/50 text-muted-foreground hover:border-primary/50'
                "
                @click="activeCategory = null"
            >
                Semua
            </button>
            <button
                v-for="category in categories"
                :key="category.id"
                type="button"
                class="rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors"
                :class="
                    activeCategory === category.id
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-border/50 text-muted-foreground hover:border-primary/50'
                "
                @click="activeCategory = category.id"
            >
                {{ category.name }}
            </button>
        </div>

        <div
            v-if="filteredProducts && filteredProducts.length > 0"
            class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"
        >
            <button
                v-for="product in filteredProducts"
                :key="product.id"
                class="flex h-full flex-col overflow-hidden rounded-xl border-2 bg-secondary text-left shadow-sm transition-all"
                :class="
                    selectedProduct === product.id
                        ? 'border-primary ring-2 ring-primary/30'
                        : 'border-border/50 hover:border-primary/50'
                "
                @click="emit('update:selectedProduct', product.id)"
            >
                <div
                    class="flex items-start justify-between gap-2 pt-4 pr-3 pl-4"
                >
                    <span class="text-sm font-semibold text-foreground">
                        {{ product.name }}
                    </span>
                    <span
                        v-if="product.flash_price != null"
                        class="shrink-0 rounded-full bg-destructive px-1.5 py-0.5 text-[10px] font-bold text-white"
                    >
                        -{{ product.flash_discount_percent }}%
                    </span>
                </div>
                <div
                    class="mt-2 flex flex-1 flex-col justify-between text-sm font-bold text-primary"
                >
                    <div class="flex items-center gap-2 pl-4 md:gap-3">
                        <div class="flex aspect-square w-6 items-center md:w-8">
                            <img
                                v-if="product.image"
                                :src="product.image"
                                :alt="product.name"
                                class="h-8 object-cover"
                            />
                        </div>
                        <div>
                            <span
                                v-if="product.flash_price != null"
                                class="block text-xs font-medium text-muted-foreground line-through"
                            >
                                {{
                                    formatCurrency(
                                        product.flash_original_price ??
                                            product.sell_price,
                                    )
                                }}
                            </span>
                            <span
                                class="flex items-center text-[14px] font-semibold text-primary md:text-[16px]"
                            >
                                {{
                                    formatCurrency(
                                        product.flash_price ??
                                            product.sell_price,
                                    )
                                }}</span
                            >
                        </div>
                    </div>
                    <div
                        class="mt-5 flex w-full flex-col gap-2 bg-card p-4 text-xs font-semibold text-foreground"
                    >
                        <div
                            class="flex items-center gap-2"
                            v-if="product.delay"
                        >
                            <ClockIcon
                                class="h-4 w-4 flex-shrink-0 text-muted-foreground"
                            />
                            <span>Delay</span>
                        </div>
                        <span
                            v-safe-html="product.description"
                            class="text-muted-foreground"
                        ></span>
                    </div>
                </div>
            </button>
        </div>
        <div v-else class="py-8 text-center text-sm text-muted-foreground">
            {{
                activeCategory !== null
                    ? 'Tidak ada produk pada kategori ini'
                    : 'Tidak ada produk tersedia'
            }}
        </div>
    </section>
</template>
