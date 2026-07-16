<script setup lang="ts">
import { show as showGiftOrder } from '@/actions/App/Http/Controllers/Cms/Order/GiftOrderController';
import { show as showManualOrder } from '@/actions/App/Http/Controllers/Cms/Order/ManualTopupOrderController';
import { show as showTopupOrder } from '@/actions/App/Http/Controllers/Cms/Order/OrderController';
import Heading from '@/components/Heading.vue';
import ResourceTable from '@/components/ResourceTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import useNewOrdersQuery from '@/composables/query/useNewOrdersQuery';
import { useFilter } from '@/composables/useFilter';
import useOrderAlert from '@/composables/useOrderAlert';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/lib/utils';
import { PaginationItem, type BreadcrumbItem } from '@/types';
import { OrderDataItem } from '@/types/cms/main';
import { Head, Link, router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { Bell, BellOff, BellRing } from 'lucide-vue-next';
import { Eye } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps<{
    data: PaginationItem<OrderDataItem>;
    orderBy?: string;
    order?: 'asc' | 'desc';
    search?: string;
    paginate?: number;
    resource: string;
    type?: string;
    paymentStatusFilter?: string[];
    dateFrom?: string;
    dateTo?: string;
    lastOrderId: number;
}>();

const { updateParams } = useFilter();

const title = 'All Orders';
const description =
    'Gabungan semua order: Topup, Gift, dan Manual Topup dalam satu tabel.';

const breadcrumbItems: BreadcrumbItem[] = [{ title: title, href: '#' }];

const typeTabs = [
    { value: 'all', label: 'All' },
    { value: 'topup', label: 'Topup' },
    { value: 'gift', label: 'Gift' },
    { value: 'manual', label: 'Manual' },
];

const typeBadgeVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    topup: 'default',
    gift: 'secondary',
    manual: 'outline',
};

const typeBadgeClass: Record<string, string> = {
    topup: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    gift: 'bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-400',
    manual: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
};

const typeLabel: Record<string, string> = {
    topup: 'Topup',
    gift: 'Gift',
    manual: 'Manual',
    unknown: 'Unknown',
};

const paymentStatusOptions = [
    { label: 'Pending', value: 'pending' },
    { label: 'Settlement', value: 'settlement' },
    { label: 'Failed', value: 'failed' },
];

const columns = [
    { label: 'Type', key: 'order_type', sortable: false },
    { label: 'Reference', key: 'reference', sortable: true },
    { label: 'Customer', key: 'name', sortable: true },
    { label: 'Product', key: 'product', sortable: false },
    { label: 'Amount', key: 'total_amount', sortable: true },
    { label: 'Payment Method', key: 'payment_method', sortable: false },
    { label: 'Payment Status', key: 'payment_status', sortable: true },
    { label: 'Created At', key: 'created_at', sortable: true },
    {
        label: 'Actions',
        key: 'actions',
        sortable: false,
        class: 'w-24 text-center',
    },
];

const switchType = (type: string) => {
    updateParams({ type, page: 1 });
};

const togglePaymentStatus = (value: string) => {
    const current = props.paymentStatusFilter || [];
    const next = current.includes(value)
        ? current.filter((v) => v !== value)
        : [...current, value];
    updateParams({
        payment_status: next.length > 0 ? next : undefined,
        page: 1,
    });
};

const changeDateFrom = (value: string) => {
    updateParams({ date_from: value || undefined, page: 1 });
};

const changeDateTo = (value: string) => {
    updateParams({ date_to: value || undefined, page: 1 });
};

const detailUrl = (row: OrderDataItem) => {
    if (row.order_type === 'gift')
        return showGiftOrder({ order: row.reference }).url;
    if (row.order_type === 'manual')
        return showManualOrder({ order: row.reference }).url;

    return showTopupOrder({ order: row.reference }).url;
};

// New-order alert: sound + Windows/desktop notification (see
// resources/js/composables/useOrderAlert.ts and useNewOrdersQuery.ts).
const lastSeenOrderId = ref(props.lastOrderId ?? 0);
const {
    soundEnabled,
    notificationPermission,
    toggleSound,
    unlockAudio,
    playChime,
    requestNotificationPermission,
    showDesktopNotification,
} = useOrderAlert();

const { data: newOrdersPoll } = useNewOrdersQuery(lastSeenOrderId);

watch(newOrdersPoll, (result) => {
    if (!result || result.orders.length === 0) return;

    for (const order of result.orders) {
        playChime();
        showDesktopNotification(
            order.id,
            'Order Baru Masuk',
            `${order.name} — ${typeLabel[order.order_type] ?? order.order_type} — ${formatCurrency(order.total_amount)}`,
            () => router.visit(detailUrl(order as unknown as OrderDataItem)),
        );
    }

    lastSeenOrderId.value = result.last_id;

    // Refresh the table in place so the new order shows up without losing
    // the admin's current filters/scroll position.
    router.reload({ only: ['data'] });
});

const unlockAudioOnce = () => {
    unlockAudio();
    document.removeEventListener('click', unlockAudioOnce);
};

onMounted(() => {
    document.addEventListener('click', unlockAudioOnce);
});

onUnmounted(() => {
    document.removeEventListener('click', unlockAudioOnce);
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="title" />
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <Heading :title="title" :description="description" />

                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :title="
                            soundEnabled
                                ? 'Matikan notifikasi suara'
                                : 'Nyalakan notifikasi suara'
                        "
                        @click="toggleSound"
                    >
                        <Bell v-if="soundEnabled" class="h-4 w-4" />
                        <BellOff v-else class="h-4 w-4" />
                        {{ soundEnabled ? 'Suara Aktif' : 'Suara Nonaktif' }}
                    </Button>

                    <Button
                        v-if="notificationPermission === 'default'"
                        variant="outline"
                        size="sm"
                        @click="requestNotificationPermission"
                    >
                        <BellRing class="h-4 w-4" />
                        Aktifkan Notifikasi Windows
                    </Button>
                    <span
                        v-else-if="notificationPermission === 'denied'"
                        class="text-xs text-muted-foreground"
                    >
                        Notifikasi Windows diblokir browser
                    </span>
                </div>
            </div>

            <!-- Type Tabs -->
            <div class="flex flex-wrap items-center gap-2">
                <button
                    v-for="tab in typeTabs"
                    :key="tab.value"
                    type="button"
                    class="rounded-lg border px-4 py-2 text-sm font-semibold transition-colors"
                    :class="
                        (type || 'all') === tab.value
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border/50 text-muted-foreground hover:border-primary/50'
                    "
                    @click="switchType(tab.value)"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- Payment Status + Date Range Filters -->
            <div
                class="flex flex-wrap items-end gap-6 rounded-lg border border-border/50 p-4"
            >
                <div class="flex flex-col gap-2">
                    <span class="text-sm font-medium text-foreground">
                        Payment Status
                    </span>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-for="option in paymentStatusOptions"
                            :key="option.value"
                            variant="outline"
                            size="sm"
                            :class="{
                                'bg-primary text-primary-foreground hover:bg-primary/90':
                                    (paymentStatusFilter || []).includes(
                                        option.value,
                                    ),
                            }"
                            @click="togglePaymentStatus(option.value)"
                        >
                            {{ option.label }}
                        </Button>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="date_from" class="text-sm font-medium">
                        Dari Tanggal
                    </Label>
                    <Input
                        id="date_from"
                        type="date"
                        class="w-40"
                        :model-value="dateFrom"
                        @change="
                            changeDateFrom(
                                ($event.target as HTMLInputElement).value,
                            )
                        "
                    />
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="date_to" class="text-sm font-medium">
                        Sampai Tanggal
                    </Label>
                    <Input
                        id="date_to"
                        type="date"
                        class="w-40"
                        :model-value="dateTo"
                        @change="
                            changeDateTo(
                                ($event.target as HTMLInputElement).value,
                            )
                        "
                    />
                </div>
            </div>

            <ResourceTable
                :data="data"
                :columns="columns"
                :order-by="orderBy"
                :order="order"
                :search="search"
                :paginate="paginate"
                key-field="reference"
            >
                <template #order_type="{ row }">
                    <Badge
                        :variant="
                            typeBadgeVariant[row.order_type ?? ''] ?? 'outline'
                        "
                        :class="typeBadgeClass[row.order_type ?? '']"
                    >
                        {{ typeLabel[row.order_type ?? 'unknown'] }}
                    </Badge>
                </template>
                <template #name="{ row }">
                    <div class="flex flex-col">
                        <span class="font-medium">{{ row.name }}</span>
                        <span class="text-sm text-muted-foreground">
                            {{ row.phone }} | {{ row.email || '-' }}
                        </span>
                    </div>
                </template>
                <template #product="{ row }">
                    <div class="flex flex-col">
                        <span class="font-medium">{{
                            row.product?.name ?? '-'
                        }}</span>
                        <span class="text-xs text-muted-foreground">
                            {{ row.brand?.name ?? '-' }}
                        </span>
                    </div>
                </template>
                <template #total_amount="{ row }">
                    {{ formatCurrency(row.total_amount) }}
                </template>
                <template #payment_method="{ row }">
                    <span
                        class="inline-flex w-fit items-center rounded-md px-2.5 py-1 text-xs font-semibold tracking-wide uppercase"
                        :class="{
                            'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400':
                                row.payment?.driver === 'midtrans',
                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400':
                                row.payment?.driver === 'linkqu',
                            'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400':
                                row.payment?.driver === 'manual',
                            'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400':
                                row.payment?.driver === 'balance',
                            'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400':
                                !row.payment?.driver,
                        }"
                    >
                        {{
                            row.payment?.driver === 'balance'
                                ? 'Saldo'
                                : row.payment?.driver || 'N/A'
                        }}
                    </span>
                </template>
                <template #payment_status="{ row }">
                    <span
                        class="inline-flex w-fit items-center rounded-md px-2.5 py-1 text-xs font-semibold"
                        :class="{
                            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400':
                                row.payment_status === 2 ||
                                row.payment_status === 1,
                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400':
                                row.payment_status === 0,
                            'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400':
                                row.payment_status < 0,
                        }"
                    >
                        {{
                            row.payment_status === 2 || row.payment_status === 1
                                ? 'Successful'
                                : row.payment_status === 0
                                  ? 'Pending'
                                  : 'Failed'
                        }}
                    </span>
                </template>
                <template #created_at="{ row }">
                    {{ dayjs(row.created_at).format('DD MMMM YYYY HH:mm:ss') }}
                </template>
                <template #actions="{ row }">
                    <div class="flex items-center justify-center">
                        <Link :href="detailUrl(row)">
                            <Button
                                variant="ghost"
                                size="icon"
                                title="View Details"
                            >
                                <Eye class="h-4 w-4" />
                            </Button>
                        </Link>
                    </div>
                </template>
            </ResourceTable>
        </div>
    </AppLayout>
</template>
