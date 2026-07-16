<script setup lang="ts">
import { index } from '@/actions/App/Http/Controllers/Main/HomeController';
import BrandCard from '@/components/BrandCard.vue';
import FlashSaleSection from '@/components/FlashSaleSection.vue';
import HeroBanner from '@/components/HeroBanner.vue';
import MainFooter from '@/components/MainFooter.vue';
import MainHeader from '@/components/MainHeader.vue';
import Maintenance from '@/pages/main/Maintenance.vue';
import { PaginationItem } from '@/types';
import { PPOBBrandDataItem, PPOBCategoryDataItem } from '@/types/cms/ppob';
import { SliderDataItem } from '@/types/cms/web';
import { Head, InfiniteScroll, Link, usePage } from '@inertiajs/vue3';

defineProps<{
    sliders: SliderDataItem[];
    brands: PaginationItem<PPOBBrandDataItem>;
    featured_brands: PPOBBrandDataItem[];
    categories: PPOBCategoryDataItem[];
    active_flash_sale: any | null;
    search?: string | null;
    category?: string | null;
}>();

const page = usePage();
const setting = page.props.setting;
const appUrl = page.props.app_url;
</script>

<template>
    <Head>
        <title>{{ setting?.title }}</title>
        <component :is="'script'" type="application/ld+json">
            {{
                JSON.stringify({
                    '@context': 'https://schema.org',
                    '@type': 'WebSite',
                    name: setting?.title,
                    url: index().url,
                    potentialAction: {
                        '@type': 'SearchAction',
                        target: {
                            '@type': 'EntryPoint',
                            urlTemplate: `${index().url}?search={search_term_string}`,
                        },
                        'query-input': 'required name=search_term_string',
                    },
                })
            }}
        </component>
    </Head>

    <div class="flex min-h-screen flex-col bg-background">
        <!-- Header -->
        <MainHeader
            :show-search="true"
            :search="search"
            :popular-brands="featured_brands"
        />

        <template v-if="setting?.maintenance_status === 'active'">
            <Maintenance />
        </template>
        <!-- Main Content -->
        <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8" v-else>
            <template v-if="!search">
                <!-- Hero Banner -->
                <HeroBanner
                    class="mb-8"
                    :slides="sliders"
                    :autoplay="true"
                    :interval="50000"
                />

                <!-- Flash Sale Section -->
                <FlashSaleSection
                    v-if="active_flash_sale"
                    :flash-sale="active_flash_sale"
                />

                <!-- Categories Section -->
                <section class="mb-8">
                    <div class="mb-4">
                        <h2 class="text-xl font-bold text-foreground">
                            Kategori
                        </h2>
                    </div>
                    <div class="flex gap-3 overflow-x-auto pb-2">
                        <Link :href="index().url" preserve-scroll>
                            <button
                                class="flex min-w-[140px] flex-col items-center gap-2 rounded-lg border p-4 transition-all hover:scale-105 hover:shadow-md"
                                :class="
                                    !category
                                        ? 'border-primary bg-primary/10 hover:border-primary'
                                        : 'border-border/50 bg-card hover:border-primary/50'
                                "
                            >
                                <span class="text-3xl">🗂️</span>
                                <span
                                    class="text-sm font-semibold text-foreground"
                                >
                                    Semua
                                </span>
                                <span class="text-xs text-muted-foreground">
                                    Semua produk
                                </span>
                            </button>
                        </Link>
                        <Link
                            v-for="cat in categories"
                            :href="index().url + '?category=' + cat.slug"
                            :key="cat.id"
                            preserve-scroll
                        >
                            <button
                                class="flex min-w-[140px] flex-col items-center gap-2 rounded-lg border p-4 transition-all hover:scale-105 hover:shadow-md"
                                :class="
                                    category === cat.slug
                                        ? 'border-primary bg-primary/10 hover:border-primary'
                                        : 'border-border/50 bg-card hover:border-primary/50'
                                "
                            >
                                <img
                                    v-if="cat.image"
                                    :src="cat.image"
                                    alt="cat.name"
                                    class="h-12 w-12 object-contain"
                                />
                                <span class="text-3xl" v-else> 🎮 </span>
                                <span
                                    class="text-sm font-semibold text-foreground"
                                >
                                    {{ cat.name }}
                                </span>
                                <span class="text-xs text-muted-foreground">
                                    {{ cat.active_brands_count }} produk
                                </span>
                            </button>
                        </Link>
                    </div>
                </section>

                <!-- Featured Brands Section -->
                <section class="mb-8" v-if="featured_brands.length > 0">
                    <div class="mb-4">
                        <h2 class="text-xl font-bold text-foreground">
                            Produk Unggulan
                        </h2>
                    </div>
                    <div
                        class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6"
                    >
                        <BrandCard
                            v-for="brand in featured_brands"
                            :key="brand.id"
                            :brand="brand"
                        />
                    </div>
                </section>
            </template>

            <!-- Brands Section -->
            <section>
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-foreground">
                        <template v-if="search">
                            Hasil pencarian untuk "{{ search }}"
                        </template>
                        <template v-else> Produk Populer </template>
                    </h2>
                </div>

                <div
                    v-if="search && brands?.data.length === 0"
                    class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-border/50 py-16 text-center"
                >
                    <span class="text-4xl">🔍</span>
                    <p class="font-semibold text-foreground">
                        Game tidak ditemukan
                    </p>
                    <p class="text-sm text-muted-foreground">
                        Coba kata kunci lain untuk menemukan game favoritmu.
                    </p>
                </div>

                <InfiniteScroll v-else data="brands">
                    <div
                        class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6"
                    >
                        <BrandCard
                            v-for="brand in brands?.data"
                            :key="brand.id"
                            :brand="brand"
                        />
                    </div>
                </InfiniteScroll>
            </section>
        </main>

        <!-- Footer -->
        <MainFooter />
    </div>
</template>
