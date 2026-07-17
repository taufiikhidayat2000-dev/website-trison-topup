<script setup lang="ts">
import RevokeResellerController from '@/actions/App/Http/Controllers/Cms/Reseller/RevokeResellerController';
import Heading from '@/components/Heading.vue';
import ResourceTable from '@/components/ResourceTable.vue';
import { Button } from '@/components/ui/button';
import { useSwal } from '@/composables/useSwal';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/lib/utils';
import { PaginationItem, type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ShieldOff } from 'lucide-vue-next';

interface ResellerItem {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    balance: number;
    orders_count: number;
    total_omzet: number | null;
    total_hemat: number;
    created_at: string;
}

defineProps<{
    data: PaginationItem<ResellerItem>;
    orderBy?: string;
    order?: 'asc' | 'desc';
    search?: string;
    paginate?: number;
}>();

const title = 'Daftar Reseller';
const description =
    'Reseller aktif beserta rekap omzet dan total diskon yang diberikan.';

const columns = [
    { label: 'Name', key: 'users.name', sortable: true },
    { label: 'Email', key: 'users.email', sortable: true },
    { label: 'Saldo', key: 'users.balance', sortable: true },
    { label: 'Total Order', key: 'orders_count', sortable: false },
    { label: 'Total Omzet', key: 'total_omzet', sortable: false },
    { label: 'Total Hemat', key: 'total_hemat', sortable: false },
    {
        label: 'Actions',
        key: 'actions',
        sortable: false,
        class: 'w-20 text-center',
    },
];

const breadcrumbItems: BreadcrumbItem[] = [{ title: title, href: '#' }];

const { confirm, toast } = useSwal();

const revoke = async (reseller: ResellerItem) => {
    const result = await confirm({
        title: 'Cabut Status Reseller?',
        text: `${reseller.name} tidak akan lagi mendapat harga reseller pada transaksi berikutnya.`,
        icon: 'warning',
        confirmButtonText: 'Ya, Cabut',
        cancelButtonText: 'Batal',
    });

    if (result.isConfirmed) {
        router.patch(
            RevokeResellerController(reseller.id).url,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.fire({
                        icon: 'success',
                        title: 'Status reseller dicabut.',
                    });
                },
            },
        );
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="title" />
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Heading :title="title" :description="description" />

            <ResourceTable
                :data="data"
                :columns="columns"
                :order-by="orderBy"
                :order="order"
                :search="search"
                :paginate="paginate"
            >
                <template #users.name="{ row }">
                    {{ row.name }}
                </template>
                <template #users.email="{ row }">
                    {{ row.email }}
                </template>
                <template #users.balance="{ row }">
                    {{ formatCurrency(row.balance) }}
                </template>
                <template #orders_count="{ row }">
                    {{ row.orders_count ?? 0 }}
                </template>
                <template #total_omzet="{ row }">
                    {{ formatCurrency(row.total_omzet ?? 0) }}
                </template>
                <template #total_hemat="{ row }">
                    {{ formatCurrency(row.total_hemat ?? 0) }}
                </template>
                <template #actions="{ row }">
                    <div class="flex items-center justify-center">
                        <Button
                            variant="ghost"
                            size="icon"
                            title="Cabut status reseller"
                            @click="revoke(row)"
                        >
                            <ShieldOff class="h-4 w-4 text-destructive" />
                        </Button>
                    </div>
                </template>
            </ResourceTable>
        </div>
    </AppLayout>
</template>
