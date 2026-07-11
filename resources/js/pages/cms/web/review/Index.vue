<script setup lang="ts">
import { show } from '@/actions/App/Http/Controllers/Cms/Web/ReviewController';
import Heading from '@/components/Heading.vue';
import ResourceTable from '@/components/ResourceTable.vue';
import ReviewChart from '@/components/review/ReviewChart.vue';
import ReviewStatsCards, {
    ReviewStats,
} from '@/components/review/ReviewStatsCards.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Label from '@/components/ui/label/Label.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useFilter } from '@/composables/useFilter';
import { usePermission } from '@/composables/usePermission';
import { useSwal } from '@/composables/useSwal';
import AppLayout from '@/layouts/AppLayout.vue';
import { PaginationItem, type BreadcrumbItem } from '@/types';
import { ReviewDataItem } from '@/types/cms/review';
import { Head, router } from '@inertiajs/vue3';
import { ModalLink } from '@inertiaui/modal-vue';
import dayjs from 'dayjs';
import { Download, Eye, EyeOff, Star, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    data: PaginationItem<ReviewDataItem>;
    games: string[];
    orderBy?: string;
    order?: 'asc' | 'desc';
    search?: string;
    paginate?: number;
    resource: string;
    stats: ReviewStats & { chart: { date: string; count: number }[] };
    filter_rating?: number | null;
    filter_game?: string | null;
    filter_status?: string | null;
    filter_date_from?: string | null;
    filter_date_to?: string | null;
}>();

const { confirm, toast } = useSwal();
const { hasPermission } = usePermission();

const title = 'Reviews';
const description = 'Kelola ulasan dan rating dari customer.';

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Web', href: '#' },
    { title: title, href: '#' },
];

const columns = [
    { label: 'ID', key: 'id', sortable: true },
    { label: 'Invoice', key: 'invoice', sortable: false },
    { label: 'Customer', key: 'customer_name', sortable: true },
    { label: 'Game', key: 'game_name', sortable: true },
    { label: 'Produk', key: 'product_name', sortable: true },
    { label: 'Rating', key: 'rating', sortable: true },
    { label: 'Review', key: 'review', sortable: false },
    { label: 'Tanggal', key: 'created_at', sortable: true },
    { label: 'Status', key: 'status', sortable: true },
    {
        label: 'Actions',
        key: 'actions',
        sortable: false,
        class: 'w-32 text-center',
    },
];

// Filter state
const filter_rating = ref<number | null>(props.filter_rating || null);
const filter_game = ref<string | null>(props.filter_game || null);
const filter_status = ref<string | null>(props.filter_status || null);
const filter_date_from = ref<string>(props.filter_date_from || '');
const filter_date_to = ref<string>(props.filter_date_to || '');

const { updateParams } = useFilter();

watch(filter_rating, (val) =>
    updateParams({ filter_rating: val, page: 1 }),
);
watch(filter_game, (val) => updateParams({ filter_game: val, page: 1 }));
watch(filter_status, (val) =>
    updateParams({ filter_status: val, page: 1 }),
);
watch(filter_date_from, (val) =>
    updateParams({ filter_date_from: val || null, page: 1 }),
);
watch(filter_date_to, (val) =>
    updateParams({ filter_date_to: val || null, page: 1 }),
);

const exportUrl = () => {
    const params = new URLSearchParams();
    if (filter_rating.value) params.set('filter_rating', String(filter_rating.value));
    if (filter_game.value) params.set('filter_game', filter_game.value);
    if (filter_status.value) params.set('filter_status', filter_status.value);
    if (filter_date_from.value) params.set('filter_date_from', filter_date_from.value);
    if (filter_date_to.value) params.set('filter_date_to', filter_date_to.value);

    return `/cms/web/reviews/export?${params.toString()}`;
};

const setStatus = (review: ReviewDataItem, status: 'published' | 'hidden') => {
    router.patch(
        `/cms/web/reviews/${review.id}/status`,
        { status },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.fire({
                    icon: 'success',
                    title: `Review ${status === 'published' ? 'dipublikasikan' : 'disembunyikan'}.`,
                });
            },
        },
    );
};

const deleteReview = (review: ReviewDataItem) => {
    confirm({
        title: 'Hapus Review',
        text: 'Aksi ini tidak bisa dibatalkan.',
        icon: 'warning',
        confirmButtonText: 'Ya, hapus!',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/cms/web/reviews/${review.id}`, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    toast.fire({
                        icon: 'success',
                        title: 'Review deleted successfully.',
                    });
                },
            });
        }
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="title" />
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <Heading :title="title" :description="description" />
                <a :href="exportUrl()" v-if="hasPermission('export' + resource)">
                    <Button variant="outline">
                        <Download class="h-4 w-4" />
                        Export CSV
                    </Button>
                </a>
            </div>

            <ReviewStatsCards :stats="stats" />
            <ReviewChart :chart="stats.chart" />

            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div class="flex flex-col">
                    <Label class="mb-3">Rating Filter</Label>
                    <Select v-model="filter_rating">
                        <SelectTrigger class="mt-1 w-full">
                            <SelectValue placeholder="Semua Rating" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="null">
                                -- Semua Rating --
                            </SelectItem>
                            <SelectItem
                                v-for="star in [5, 4, 3, 2, 1]"
                                :key="star"
                                :value="star"
                            >
                                {{ star }} ★
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="flex flex-col">
                    <Label class="mb-3">Game Filter</Label>
                    <Select v-model="filter_game">
                        <SelectTrigger class="mt-1 w-full">
                            <SelectValue placeholder="Semua Game" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="null">
                                -- Semua Game --
                            </SelectItem>
                            <SelectItem
                                v-for="game in games"
                                :key="game"
                                :value="game"
                            >
                                {{ game }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="flex flex-col">
                    <Label class="mb-3">Status Filter</Label>
                    <Select v-model="filter_status">
                        <SelectTrigger class="mt-1 w-full">
                            <SelectValue placeholder="Semua Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="null">
                                -- Semua Status --
                            </SelectItem>
                            <SelectItem value="published">
                                Published
                            </SelectItem>
                            <SelectItem value="hidden">Hidden</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="flex flex-col gap-2">
                    <Label class="mb-1">Tanggal</Label>
                    <div class="flex items-center gap-2">
                        <Input v-model="filter_date_from" type="date" />
                        <span class="text-muted-foreground">-</span>
                        <Input v-model="filter_date_to" type="date" />
                    </div>
                </div>
            </div>

            <ResourceTable
                :data="data"
                :columns="columns"
                :order-by="orderBy"
                :order="order"
                :search="search"
                :paginate="paginate"
                key-field="id"
            >
                <template #invoice="{ row }">
                    <span class="font-mono text-xs">{{
                        row.order?.reference ?? '-'
                    }}</span>
                </template>
                <template #rating="{ row }">
                    <div class="flex items-center gap-1">
                        <Star
                            v-for="i in 5"
                            :key="i"
                            class="h-3.5 w-3.5"
                            :class="
                                i <= row.rating
                                    ? 'fill-yellow-400 text-yellow-400'
                                    : 'text-muted-foreground'
                            "
                        />
                    </div>
                </template>
                <template #review="{ row }">
                    <p class="max-w-xs truncate text-sm" :title="row.review">
                        {{ row.review }}
                    </p>
                </template>
                <template #created_at="{ row }">
                    {{ dayjs(row.created_at).format('DD MMM YYYY HH:mm') }}
                </template>
                <template #status="{ row }">
                    <span
                        :class="{
                            'rounded-full px-2 py-1 text-xs font-semibold': true,
                            'bg-green-100 text-green-800': row.status === 'published',
                            'bg-gray-100 text-gray-800': row.status === 'hidden',
                            'bg-yellow-100 text-yellow-800': row.status === 'pending',
                        }"
                    >
                        {{ row.status }}
                    </span>
                </template>
                <template #actions="{ row }">
                    <div class="flex items-center justify-center gap-1">
                        <ModalLink
                            :href="show({ review: row.id }).url"
                            slideover
                            v-if="hasPermission('show' + resource)"
                        >
                            <Button variant="ghost" size="icon" title="View">
                                <Eye class="h-4 w-4" />
                            </Button>
                        </ModalLink>
                        <Button
                            v-if="
                                row.status !== 'published' &&
                                hasPermission('update' + resource)
                            "
                            variant="ghost"
                            size="icon"
                            title="Publish"
                            @click="setStatus(row, 'published')"
                        >
                            <Eye class="h-4 w-4 text-green-600" />
                        </Button>
                        <Button
                            v-if="
                                row.status === 'published' &&
                                hasPermission('update' + resource)
                            "
                            variant="ghost"
                            size="icon"
                            title="Hide"
                            @click="setStatus(row, 'hidden')"
                        >
                            <EyeOff class="h-4 w-4 text-orange-600" />
                        </Button>
                        <Button
                            v-if="hasPermission('delete' + resource)"
                            variant="ghost"
                            size="icon"
                            title="Delete"
                            @click="deleteReview(row)"
                        >
                            <Trash2 class="h-4 w-4 text-destructive" />
                        </Button>
                    </div>
                </template>
            </ResourceTable>
        </div>
    </AppLayout>
</template>
