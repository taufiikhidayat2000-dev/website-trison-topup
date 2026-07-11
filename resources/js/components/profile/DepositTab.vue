<script setup lang="ts">
import ResourceTable from '@/components/ResourceTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatCurrency } from '@/lib/utils';
import { PaginationItem } from '@/types';
import { DepositDataItem } from '@/types/cms/deposit';
import { BalanceMutationDataItem } from '@/types/cms/member';
import { useForm } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { computed, ref } from 'vue';

defineProps<{
    balance: number;
    mutations: PaginationItem<BalanceMutationDataItem>;
    deposits: PaginationItem<DepositDataItem>;
}>();

type HistoryTab = 'mutations' | 'deposits';
const historyTab = ref<HistoryTab>('mutations');

const quickAmounts = [50000, 100000, 300000, 500000];

const channels = [
    { id: 'qris', name: 'QRIS', fee: 0.007, action: 'multiply' as const },
    {
        id: 'bca',
        name: 'Virtual Account BCA',
        fee: 4000,
        action: 'add' as const,
    },
    {
        id: 'mandiri',
        name: 'Virtual Account Mandiri',
        fee: 4000,
        action: 'add' as const,
    },
    {
        id: 'bni',
        name: 'Virtual Account BNI',
        fee: 4000,
        action: 'add' as const,
    },
    {
        id: 'bri',
        name: 'Virtual Account BRI',
        fee: 4000,
        action: 'add' as const,
    },
    {
        id: 'permata',
        name: 'Virtual Account Permata',
        fee: 4000,
        action: 'add' as const,
    },
];

const form = useForm({
    amount: '' as number | string,
    channel: null as string | null,
});

const selectedChannel = computed(() =>
    channels.find((c) => c.id === form.channel),
);

const fee = computed(() => {
    const amount = Number(form.amount) || 0;
    if (!selectedChannel.value) return 0;
    return selectedChannel.value.action === 'multiply'
        ? Math.round(amount * selectedChannel.value.fee)
        : selectedChannel.value.fee;
});

const total = computed(() => (Number(form.amount) || 0) + fee.value);

const submit = () => {
    form.post('/deposits', {
        preserveScroll: true,
    });
};

const mutationColumns = [
    { label: 'Tanggal', key: 'created_at', sortable: false },
    { label: 'Tipe', key: 'type', sortable: false },
    { label: 'Nominal', key: 'amount', sortable: false },
    { label: 'Keterangan', key: 'description', sortable: false },
];

const depositColumns = [
    { label: 'Reference', key: 'reference', sortable: false },
    { label: 'Nominal', key: 'amount', sortable: false },
    { label: 'Channel', key: 'channel', sortable: false },
    { label: 'Status', key: 'status', sortable: false },
    { label: 'Tanggal', key: 'created_at', sortable: false },
];
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-lg border border-border/50 bg-card p-6 shadow-sm">
            <h2 class="mb-6 text-lg font-bold text-foreground">Saldo Saya</h2>

            <!-- Current Balance -->
            <div
                class="mb-6 rounded-lg bg-gradient-to-r from-primary to-primary/80 p-6 text-white"
            >
                <p class="mb-2 text-sm opacity-90">Saldo Saat Ini</p>
                <p class="text-3xl font-bold">{{ formatCurrency(balance) }}</p>
            </div>

            <!-- Top Up Form -->
            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <Label for="amount">Nominal Top Up</Label>
                    <Input
                        id="amount"
                        v-model="form.amount"
                        type="number"
                        placeholder="Masukkan nominal (contoh: 100000)"
                        class="mt-1"
                    />
                    <p
                        v-if="form.errors.amount"
                        class="mt-1 text-xs text-destructive"
                    >
                        {{ form.errors.amount }}
                    </p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="quick in quickAmounts"
                            :key="quick"
                            type="button"
                            class="rounded-lg border px-3 py-1 text-xs font-medium transition-colors"
                            :class="
                                Number(form.amount) === quick
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'border-border/50 text-muted-foreground hover:border-primary/50'
                            "
                            @click="form.amount = quick"
                        >
                            {{ formatCurrency(quick) }}
                        </button>
                    </div>
                </div>

                <div>
                    <Label>Channel Pembayaran</Label>
                    <div class="mt-2 space-y-2">
                        <button
                            v-for="channel in channels"
                            :key="channel.id"
                            type="button"
                            class="flex w-full items-center justify-between rounded-lg border-2 p-4 transition-all"
                            :class="
                                form.channel === channel.id
                                    ? 'border-primary bg-primary/5'
                                    : 'border-border/50 hover:border-primary'
                            "
                            @click="form.channel = channel.id"
                        >
                            <span class="font-medium text-foreground">
                                {{ channel.name }}
                            </span>
                            <span class="text-sm text-muted-foreground">
                                Fee:
                                {{
                                    channel.action === 'multiply'
                                        ? `${(channel.fee * 100).toFixed(1)}%`
                                        : formatCurrency(channel.fee)
                                }}
                            </span>
                        </button>
                    </div>
                    <p
                        v-if="form.errors.channel"
                        class="mt-1 text-xs text-destructive"
                    >
                        {{ form.errors.channel }}
                    </p>
                </div>

                <div
                    v-if="form.amount && form.channel"
                    class="space-y-1 rounded-lg bg-muted/50 p-4 text-sm"
                >
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Nominal</span>
                        <span>{{ formatCurrency(Number(form.amount)) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Biaya Admin</span>
                        <span>{{ formatCurrency(fee) }}</span>
                    </div>
                    <div
                        class="flex justify-between border-t border-border/30 pt-1 font-bold"
                    >
                        <span>Total Bayar</span>
                        <span class="text-primary">
                            {{ formatCurrency(total) }}
                        </span>
                    </div>
                </div>

                <Button
                    type="submit"
                    class="w-full bg-primary hover:bg-primary/90"
                    :disabled="!form.amount || !form.channel || form.processing"
                >
                    Top Up Sekarang
                </Button>
            </form>
        </div>

        <div class="rounded-lg border border-border/50 bg-card p-6 shadow-sm">
            <div class="mb-4 flex gap-2">
                <button
                    type="button"
                    class="rounded-lg border px-4 py-2 text-sm font-medium transition-colors"
                    :class="
                        historyTab === 'mutations'
                            ? 'border-primary bg-primary/5 text-primary'
                            : 'border-border/50 text-muted-foreground'
                    "
                    @click="historyTab = 'mutations'"
                >
                    Mutasi Saldo
                </button>
                <button
                    type="button"
                    class="rounded-lg border px-4 py-2 text-sm font-medium transition-colors"
                    :class="
                        historyTab === 'deposits'
                            ? 'border-primary bg-primary/5 text-primary'
                            : 'border-border/50 text-muted-foreground'
                    "
                    @click="historyTab = 'deposits'"
                >
                    Riwayat Deposit
                </button>
            </div>

            <ResourceTable
                v-if="historyTab === 'mutations'"
                :data="mutations"
                :columns="mutationColumns"
                :paginate="10"
            >
                <template #created_at="{ row }">
                    {{ dayjs(row.created_at).format('DD MMM YYYY HH:mm') }}
                </template>
                <template #type="{ row }">
                    <Badge
                        :variant="
                            row.type === 'credit' ? 'default' : 'destructive'
                        "
                    >
                        {{ row.type === 'credit' ? 'Credit' : 'Debit' }}
                    </Badge>
                </template>
                <template #amount="{ row }">
                    {{ row.type === 'credit' ? '+' : '-'
                    }}{{ formatCurrency(row.amount) }}
                </template>
                <template #description="{ row }">
                    {{ row.description }}
                </template>
            </ResourceTable>

            <ResourceTable
                v-else
                :data="deposits"
                :columns="depositColumns"
                :paginate="10"
            >
                <template #reference="{ row }">
                    <span class="font-mono">{{ row.reference }}</span>
                </template>
                <template #amount="{ row }">
                    {{ formatCurrency(row.amount) }}
                </template>
                <template #channel="{ row }">
                    <span class="uppercase">{{ row.channel }}</span>
                </template>
                <template #status="{ row }">
                    <Badge
                        :variant="
                            row.status === 'paid'
                                ? 'default'
                                : row.status === 'pending'
                                  ? 'secondary'
                                  : 'destructive'
                        "
                    >
                        {{ row.status }}
                    </Badge>
                </template>
                <template #created_at="{ row }">
                    {{ dayjs(row.created_at).format('DD MMM YYYY HH:mm') }}
                </template>
            </ResourceTable>
        </div>
    </div>
</template>
