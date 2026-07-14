<script setup lang="ts">
import { show as brandShow } from '@/actions/App/Http/Controllers/Main/BrandController';
import { index as homeIndex } from '@/actions/App/Http/Controllers/Main/HomeController';
import { products as searchProducts } from '@/actions/App/Http/Controllers/Main/SearchController';
import { Input } from '@/components/ui/input';
import { PPOBBrandDataItem } from '@/types/cms/ppob';
import { router } from '@inertiajs/vue3';
import { onClickOutside, useDebounceFn } from '@vueuse/core';
import { Loader2, Search, SearchX, ShoppingBag } from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

interface SearchResultItem {
    id: number;
    name: string;
    slug: string;
    thumbnail: string | null;
    subtitle: string;
    url: string;
}

const props = withDefaults(
    defineProps<{
        search?: string | null;
        popularBrands?: PPOBBrandDataItem[];
    }>(),
    {
        search: '',
        popularBrands: () => [],
    },
);

const RECENT_SEARCHES_KEY = 'trison_recent_searches';
const MAX_RECENT_SEARCHES = 8;

const containerRef = ref<HTMLElement | null>(null);
// `search` arrives as `null` (not `undefined`) when the backend has no
// `?search=` query param, which bypasses withDefaults' default entirely.
const query = ref(props.search ?? '');
const isOpen = ref(false);
const loading = ref(false);
const results = ref<SearchResultItem[]>([]);
const activeIndex = ref(-1);
const recentSearches = ref<string[]>([]);

const popularAsResults = computed<SearchResultItem[]>(() =>
    props.popularBrands.map((brand) => ({
        id: brand.id,
        name: brand.name,
        slug: brand.slug,
        thumbnail: brand.image ?? null,
        subtitle: brand.category?.name ?? '',
        url: brandShow({ brand: brand.slug }).url,
    })),
);

const isSearching = computed(() => query.value.trim().length > 0);
const activeList = computed(() =>
    isSearching.value ? results.value : popularAsResults.value,
);

watch(
    () => props.search,
    (val) => {
        query.value = val ?? '';
    },
);

const fetchResults = useDebounceFn(async (term: string) => {
    try {
        const response = await fetch(
            searchProducts({ query: { q: term } }).url,
            { headers: { Accept: 'application/json' } },
        );

        results.value = response.ok ? await response.json() : [];
    } catch {
        results.value = [];
    } finally {
        loading.value = false;
    }
}, 300);

const onInput = (value: string) => {
    query.value = value;
    isOpen.value = true;
    activeIndex.value = -1;

    if (!value.trim()) {
        results.value = [];
        loading.value = false;
        return;
    }

    loading.value = true;
    fetchResults(value.trim());
};

const loadRecentSearches = () => {
    try {
        const stored = localStorage.getItem(RECENT_SEARCHES_KEY);
        recentSearches.value = stored ? JSON.parse(stored) : [];
    } catch {
        recentSearches.value = [];
    }
};

const saveRecentSearch = (term: string) => {
    const trimmed = term.trim();
    if (!trimmed) return;

    const next = [
        trimmed,
        ...recentSearches.value.filter(
            (item) => item.toLowerCase() !== trimmed.toLowerCase(),
        ),
    ].slice(0, MAX_RECENT_SEARCHES);

    recentSearches.value = next;
    localStorage.setItem(RECENT_SEARCHES_KEY, JSON.stringify(next));
};

const clearRecentSearches = () => {
    recentSearches.value = [];
    localStorage.removeItem(RECENT_SEARCHES_KEY);
};

const goToBrand = (item: SearchResultItem) => {
    saveRecentSearch(item.name);
    isOpen.value = false;
    router.visit(item.url);
};

const submitFullSearch = () => {
    const trimmed = query.value.trim();
    if (!trimmed) return;

    saveRecentSearch(trimmed);
    isOpen.value = false;
    router.get(
        homeIndex().url,
        { search: trimmed },
        { preserveState: true, preserveScroll: true },
    );
};

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        isOpen.value = false;
        (event.target as HTMLInputElement)?.blur();
        return;
    }

    if (event.key === 'ArrowDown') {
        if (!activeList.value.length) return;
        event.preventDefault();
        activeIndex.value = Math.min(
            activeIndex.value + 1,
            activeList.value.length - 1,
        );
        return;
    }

    if (event.key === 'ArrowUp') {
        if (!activeList.value.length) return;
        event.preventDefault();
        activeIndex.value = Math.max(activeIndex.value - 1, -1);
        return;
    }

    if (event.key === 'Enter') {
        const active = activeList.value[activeIndex.value];
        if (active) {
            event.preventDefault();
            goToBrand(active);
        } else {
            submitFullSearch();
        }
    }
};

const escapeHtml = (value: string) =>
    value.replace(
        /[&<>"']/g,
        (char) =>
            ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            })[char] as string,
    );

const highlight = (text: string) => {
    const term = query.value.trim();
    if (!term) return escapeHtml(text);

    const safeText = escapeHtml(text);
    const safeTerm = escapeHtml(term).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return safeText.replace(
        new RegExp(`(${safeTerm})`, 'ig'),
        '<mark class="bg-primary/30 text-inherit rounded-sm">$1</mark>',
    );
};

onClickOutside(containerRef, () => {
    isOpen.value = false;
});

onMounted(() => {
    loadRecentSearches();
    nextTick(() => {
        if (query.value.trim()) {
            loading.value = true;
            fetchResults(query.value.trim());
        }
    });
});
</script>

<template>
    <div ref="containerRef" class="relative max-w-xl flex-1">
        <div class="relative">
            <Search
                class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                type="search"
                :model-value="query"
                placeholder="Cari nama game"
                class="w-full border-border/50 bg-background/50 pl-9 text-foreground placeholder:text-muted-foreground"
                @update:model-value="onInput($event.toString())"
                @focus="isOpen = true"
                @keydown="onKeydown"
            />
        </div>

        <div
            v-if="isOpen"
            class="absolute top-full left-0 z-50 mt-2 w-full overflow-hidden rounded-xl border border-border/50 bg-popover text-popover-foreground shadow-lg"
        >
            <!-- Idle state: recent searches + popular games -->
            <template v-if="!isSearching">
                <div
                    v-if="recentSearches.length"
                    class="border-b border-border/50 p-3"
                >
                    <div class="mb-2 flex items-center justify-between">
                        <span
                            class="text-xs font-semibold text-muted-foreground"
                        >
                            Pencarian Terakhir
                        </span>
                        <button
                            type="button"
                            class="text-xs text-muted-foreground hover:text-foreground"
                            @click="clearRecentSearches"
                        >
                            Hapus
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="term in recentSearches"
                            :key="term"
                            type="button"
                            class="rounded-full border border-border/50 px-3 py-1 text-xs text-foreground transition-colors duration-200 hover:bg-muted"
                            @click="onInput(term)"
                        >
                            {{ term }}
                        </button>
                    </div>
                </div>

                <div
                    v-if="popularAsResults.length"
                    class="max-h-[450px] overflow-y-auto p-2"
                >
                    <div
                        class="mb-1 px-2 text-xs font-semibold text-muted-foreground"
                    >
                        🔥 Game Populer
                    </div>
                    <button
                        v-for="(item, i) in popularAsResults"
                        :key="item.id"
                        type="button"
                        class="flex w-full cursor-pointer items-center gap-3 rounded-lg p-2 text-left transition-colors duration-200 hover:bg-muted"
                        :class="{ 'bg-muted': activeIndex === i }"
                        @click="goToBrand(item)"
                        @mouseenter="activeIndex = i"
                    >
                        <img
                            v-if="item.thumbnail"
                            :src="item.thumbnail"
                            :alt="item.name"
                            class="h-[60px] w-[60px] shrink-0 rounded-lg object-cover"
                        />
                        <div
                            v-else
                            class="flex h-[60px] w-[60px] shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground"
                        >
                            <ShoppingBag class="h-6 w-6 opacity-40" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-bold text-foreground">
                                {{ item.name }}
                            </p>
                            <p
                                v-if="item.subtitle"
                                class="truncate text-xs text-muted-foreground"
                            >
                                {{ item.subtitle }}
                            </p>
                        </div>
                    </button>
                </div>

                <div
                    v-if="!recentSearches.length && !popularAsResults.length"
                    class="p-6 text-center text-sm text-muted-foreground"
                >
                    Mulai ketik untuk mencari game favoritmu
                </div>
            </template>

            <!-- Search mode -->
            <template v-else>
                <div
                    v-if="loading"
                    class="flex items-center gap-2 p-4 text-sm text-muted-foreground"
                >
                    <Loader2 class="h-4 w-4 animate-spin" />
                    Searching...
                </div>

                <div
                    v-else-if="results.length === 0"
                    class="flex flex-col items-center gap-2 p-6 text-center"
                >
                    <SearchX class="h-8 w-8 text-muted-foreground" />
                    <p class="text-sm text-muted-foreground">
                        Game tidak ditemukan
                    </p>
                </div>

                <div v-else class="max-h-[450px] overflow-y-auto p-2">
                    <button
                        v-for="(item, i) in results"
                        :key="item.id"
                        type="button"
                        class="flex w-full cursor-pointer items-center gap-3 rounded-lg p-2 text-left transition-colors duration-200 hover:bg-muted"
                        :class="{ 'bg-muted': activeIndex === i }"
                        @click="goToBrand(item)"
                        @mouseenter="activeIndex = i"
                    >
                        <img
                            v-if="item.thumbnail"
                            :src="item.thumbnail"
                            :alt="item.name"
                            class="h-[60px] w-[60px] shrink-0 rounded-lg object-cover"
                        />
                        <div
                            v-else
                            class="flex h-[60px] w-[60px] shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground"
                        >
                            <ShoppingBag class="h-6 w-6 opacity-40" />
                        </div>
                        <div class="min-w-0">
                            <p
                                class="truncate font-bold text-foreground"
                                v-html="highlight(item.name)"
                            ></p>
                            <p
                                v-if="item.subtitle"
                                class="truncate text-xs text-muted-foreground"
                            >
                                {{ item.subtitle }}
                            </p>
                        </div>
                    </button>
                </div>
            </template>
        </div>
    </div>
</template>
