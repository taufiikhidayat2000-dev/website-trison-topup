<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import ResourceTable from '@/components/ResourceTable.vue';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useFilter } from '@/composables/useFilter';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/lib/utils';
import { PaginationItem, type BreadcrumbItem } from '@/types';
import { DepositDataItem } from '@/types/cms/deposit';
import { Head } from '@inertiajs/vue3';
import dayjs from 'dayjs';

defineProps<{
    data: PaginationItem<DepositDataItem>;
    orderBy?: string;
    order?: 'asc' | 'desc';
    search?: string;
    paginate?: number;
    status?: string;
    resource: string;
}>();

const { updateParams } = useFilter();

const title = 'Deposits';
const description = 'Riwayat top up saldo member melalui LinkQu.';

const statusVariant: Record<string, 'default' | 'destructive' | 'secondary'> = {
    pending: 'secondary',
    paid: 'default',
    expired: 'destructive',
    failed: 'destructive',
};

const columns = [
    { label: 'Reference', key: 'reference', sortable: true },
    { label: 'Member', key: 'user', sortable: false },
    { label: 'Amount', key: 'amount', sortable: true },
    { label: 'Total Bayar', key: 'total_pay', sortable: true },
    { label: 'Channel', key: 'channel', sortable: false },
    { label: 'Gateway', key: 'driver', sortable: false },
    { label: 'Status', key: 'status', sortable: false },
    { label: 'Created At', key: 'created_at', sortable: true },
];

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Members', href: '#' },
    { title: title, href: '#' },
];

const changeStatus = (value: string) => {
    updateParams({ status: value === 'all' ? '' : value, page: 1 });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="title" />
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <Heading :title="title" :description="description" />
                <Select
                    :model-value="status || 'all'"
                    @update:model-value="(v) => changeStatus(String(v))"
                >
                    <SelectTrigger class="w-40">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua Status</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="paid">Paid</SelectItem>
                        <SelectItem value="expired">Expired</SelectItem>
                        <SelectItem value="failed">Failed</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <ResourceTable
                :data="data"
                :columns="columns"
                :order-by="orderBy"
                :order="order"
                :search="search"
                :paginate="paginate"
            >
                <template #reference="{ row }">
                    <span class="font-mono">{{ row.reference }}</span>
                </template>
                <template #user="{ row }">
                    <div>
                        <div class="font-medium">{{ row.user?.name }}</div>
                        <div class="text-xs text-muted-foreground">
                            {{ row.user?.email }}
                        </div>
                    </div>
                </template>
                <template #amount="{ row }">
                    {{ formatCurrency(row.amount) }}
                </template>
                <template #total_pay="{ row }">
                    {{ formatCurrency(row.total_pay) }}
                </template>
                <template #channel="{ row }">
                    <span class="uppercase">{{ row.channel }}</span>
                </template>
                <template #driver="{ row }">
                    <span class="uppercase">{{ row.driver }}</span>
                </template>
                <template #status="{ row }">
                    <Badge :variant="statusVariant[row.status] ?? 'secondary'">
                        {{ row.status }}
                    </Badge>
                </template>
                <template #created_at="{ row }">
                    {{ dayjs(row.created_at).format('DD MMMM YYYY HH:mm') }}
                </template>
            </ResourceTable>
        </div>
    </AppLayout>
</template>
