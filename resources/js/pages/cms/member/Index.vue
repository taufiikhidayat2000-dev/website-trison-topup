<script setup lang="ts">
import { show } from '@/actions/App/Http/Controllers/Cms/Member/MemberController';
import Heading from '@/components/Heading.vue';
import ResourceTable from '@/components/ResourceTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/lib/utils';
import { PaginationItem, type BreadcrumbItem } from '@/types';
import { MemberDataItem } from '@/types/cms/member';
import { Head, Link } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { Eye } from 'lucide-vue-next';

defineProps<{
    data: PaginationItem<MemberDataItem>;
    orderBy?: string;
    order?: 'asc' | 'desc';
    search?: string;
    paginate?: number;
    resource: string;
}>();

const title = 'Members';
const description = 'Kelola member/pengguna, saldo, dan status akun.';

const columns = [
    { label: 'Name', key: 'users.name', sortable: true },
    { label: 'Email', key: 'users.email', sortable: true },
    { label: 'Phone', key: 'users.phone', sortable: true },
    { label: 'Balance', key: 'users.balance', sortable: true },
    { label: 'Orders', key: 'orders_count', sortable: false },
    { label: 'Registered', key: 'users.created_at', sortable: true },
    { label: 'Status', key: 'is_active', sortable: false },
    {
        label: 'Actions',
        key: 'actions',
        sortable: false,
        class: 'w-20 text-center',
    },
];

const breadcrumbItems: BreadcrumbItem[] = [{ title: title, href: '#' }];
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
                <template #users.phone="{ row }">
                    {{ row.phone ?? '-' }}
                </template>
                <template #users.balance="{ row }">
                    {{ formatCurrency(row.balance) }}
                </template>
                <template #orders_count="{ row }">
                    {{ row.orders_count ?? 0 }}
                </template>
                <template #users.created_at="{ row }">
                    {{ dayjs(row.created_at).format('DD MMMM YYYY HH:mm') }}
                </template>
                <template #is_active="{ row }">
                    <Badge :variant="row.is_active ? 'default' : 'destructive'">
                        {{ row.is_active ? 'Active' : 'Inactive' }}
                    </Badge>
                </template>
                <template #actions="{ row }">
                    <div class="flex items-center justify-center">
                        <Link :href="show(row.id).url">
                            <Button variant="ghost" size="icon">
                                <Eye class="h-4 w-4" />
                            </Button>
                        </Link>
                    </div>
                </template>
            </ResourceTable>
        </div>
    </AppLayout>
</template>
