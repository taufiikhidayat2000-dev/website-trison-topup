<script setup lang="ts">
import {
    create,
    destroy,
    edit,
} from '@/actions/App/Http/Controllers/Cms/Marketing/FlashSaleController';
import Heading from '@/components/Heading.vue';
import ResourceTable from '@/components/ResourceTable.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { usePermission } from '@/composables/usePermission';
import { useSwal } from '@/composables/useSwal';
import { formatCurrency } from '@/lib/utils';
import AppLayout from '@/layouts/AppLayout.vue';
import { PaginationItem, type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ModalLink } from '@inertiaui/modal-vue';
import dayjs from 'dayjs';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';

const props = defineProps<{
    data: PaginationItem<any>;
    orderBy?: string;
    order?: 'asc' | 'desc';
    search?: string;
    paginate?: number;
    resource: string;
    stats: {
        active_count: number;
        total_products: number;
        total_sold: number;
        revenue: number;
        remaining_stock: number;
        nearest_end_time: string | null;
    };
}>();

const { confirm, toast } = useSwal();
const { hasPermission } = usePermission();

const title = 'Flash Sale';
const description =
    'Kelola Flash Sale: jadwal otomatis aktif/berakhir, produk, dan harga promo.';

const columns = [
    { label: 'Judul', key: 'title', sortable: true },
    { label: 'Status', key: 'status', sortable: true },
    { label: 'Produk', key: 'products_count', sortable: false },
    { label: 'Mulai', key: 'start_time', sortable: true },
    { label: 'Berakhir', key: 'end_time', sortable: true },
    {
        label: 'Aksi',
        key: 'actions',
        sortable: false,
        class: 'w-24 text-center',
    },
];

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Marketing', href: '#' },
    { title: title, href: '#' },
];

const statusColor: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    scheduled: 'bg-blue-100 text-blue-800',
    active: 'bg-green-100 text-green-800',
    ended: 'bg-slate-200 text-slate-800',
    disabled: 'bg-red-100 text-red-800',
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="title" />
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <Heading :title="title" :description="description" />
                <ModalLink
                    :href="create().url"
                    max-width="5xl"
                    slideover
                    v-if="hasPermission('create' + resource)"
                >
                    <Button>
                        <Plus class="h-4 w-4" />
                        Buat Flash Sale
                    </Button>
                </ModalLink>
            </div>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs text-muted-foreground">Flash Sale Aktif</CardTitle>
                    </CardHeader>
                    <CardContent class="text-xl font-bold">{{ stats.active_count }}</CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs text-muted-foreground">Total Produk</CardTitle>
                    </CardHeader>
                    <CardContent class="text-xl font-bold">{{ stats.total_products }}</CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs text-muted-foreground">Total Terjual</CardTitle>
                    </CardHeader>
                    <CardContent class="text-xl font-bold">{{ stats.total_sold }}</CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs text-muted-foreground">Omzet Flash Sale</CardTitle>
                    </CardHeader>
                    <CardContent class="text-xl font-bold">{{ formatCurrency(stats.revenue) }}</CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs text-muted-foreground">Sisa Stock</CardTitle>
                    </CardHeader>
                    <CardContent class="text-xl font-bold">{{ stats.remaining_stock }}</CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs text-muted-foreground">Berakhir Dalam</CardTitle>
                    </CardHeader>
                    <CardContent class="text-sm font-bold">
                        {{
                            stats.nearest_end_time
                                ? dayjs(stats.nearest_end_time).format('DD MMM HH:mm')
                                : '-'
                        }}
                    </CardContent>
                </Card>
            </div>

            <ResourceTable
                :data="data"
                :columns="columns"
                :order-by="orderBy"
                :order="order"
                :search="search"
                :paginate="paginate"
            >
                <template #status="{ row }">
                    <span
                        class="rounded-full px-2 py-1 text-xs font-semibold"
                        :class="statusColor[row.status] ?? statusColor.draft"
                    >
                        {{ row.status }}
                    </span>
                </template>
                <template #start_time="{ row }">
                    {{ dayjs(row.start_time).format('DD MMM YYYY HH:mm') }}
                </template>
                <template #end_time="{ row }">
                    {{ dayjs(row.end_time).format('DD MMM YYYY HH:mm') }}
                </template>
                <template #actions="{ row }">
                    <div class="flex items-center justify-center gap-2">
                        <ModalLink
                            :href="edit({ flash_sale: row.id }).url"
                            max-width="6xl"
                            slideover
                            v-if="hasPermission('update' + resource)"
                        >
                            <Button variant="ghost" size="icon">
                                <Pencil class="h-4 w-4" />
                            </Button>
                        </ModalLink>
                        <Button
                            variant="ghost"
                            size="icon"
                            v-if="hasPermission('delete' + resource)"
                            @click="
                                confirm({
                                    title: 'Hapus Flash Sale',
                                    text: 'Tindakan ini tidak bisa dibatalkan.',
                                    icon: 'warning',
                                    confirmButtonText: 'Ya, hapus!',
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        router.delete(
                                            destroy({ flash_sale: row.id }).url,
                                            {
                                                preserveScroll: true,
                                                preserveState: true,
                                                onSuccess: () => {
                                                    toast.fire({
                                                        icon: 'success',
                                                        title: 'Flash Sale berhasil dihapus.',
                                                    });
                                                },
                                            },
                                        );
                                    }
                                })
                            "
                        >
                            <Trash2 class="h-4 w-4 text-destructive" />
                        </Button>
                    </div>
                </template>
            </ResourceTable>
        </div>
    </AppLayout>
</template>
