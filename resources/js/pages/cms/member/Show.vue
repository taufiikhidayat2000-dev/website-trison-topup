<script setup lang="ts">
import AdjustMemberBalanceController from '@/actions/App/Http/Controllers/Cms/Member/AdjustMemberBalanceController';
import ResetMemberPasswordController from '@/actions/App/Http/Controllers/Cms/Member/ResetMemberPasswordController';
import UpdateMemberStatusController from '@/actions/App/Http/Controllers/Cms/Member/UpdateMemberStatusController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { useFilter } from '@/composables/useFilter';
import { useSwal } from '@/composables/useSwal';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/lib/utils';
import { PaginationItem, type BreadcrumbItem } from '@/types';
import { DepositDataItem } from '@/types/cms/deposit';
import { OrderDataItem } from '@/types/cms/main';
import { BalanceMutationDataItem, MemberDataItem } from '@/types/cms/member';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { KeyRound, Wallet } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    member: MemberDataItem;
    mutations: PaginationItem<BalanceMutationDataItem>;
    orders: PaginationItem<OrderDataItem>;
    deposits: PaginationItem<DepositDataItem>;
}>();

const title = `Member - ${props.member.name}`;

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Members', href: '/cms/members' },
    { title: props.member.name, href: '#' },
];

const { confirm, toast } = useSwal();
const { updateParams } = useFilter();
const page = usePage();

const newPassword = computed(() => page.props.newPassword as string | null);

// Adjust balance dialog
const balanceDialogOpen = ref(false);
const balanceForm = useForm({
    type: 'credit' as 'credit' | 'debit',
    amount: 0,
    description: '',
});

const submitBalanceAdjustment = () => {
    balanceForm.post(AdjustMemberBalanceController(props.member.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            balanceDialogOpen.value = false;
            balanceForm.reset();
            toast.fire({
                icon: 'success',
                title: 'Saldo berhasil disesuaikan.',
            });
        },
    });
};

// Reset password
const resetPassword = async () => {
    const result = await confirm({
        title: 'Reset Password Member?',
        text: 'Password baru akan dibuat otomatis dan ditampilkan sekali. Pastikan Anda mencatatnya.',
        icon: 'warning',
        confirmButtonText: 'Ya, Reset',
        cancelButtonText: 'Batal',
    });

    if (result.isConfirmed) {
        router.post(
            ResetMemberPasswordController(props.member.id).url,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    if (newPassword.value) {
                        confirm({
                            title: 'Password Baru',
                            html: `Password baru untuk <strong>${props.member.name}</strong>:<br/><code class="text-lg font-bold">${newPassword.value}</code><br/><br/>Password ini hanya ditampilkan sekali.`,
                            icon: 'success',
                            showCancelButton: false,
                            confirmButtonText: 'Sudah Dicatat',
                        });
                    }
                },
            },
        );
    }
};

// Toggle status
const toggleStatus = async () => {
    const activating = !props.member.is_active;
    const result = await confirm({
        title: activating ? 'Aktifkan Member?' : 'Nonaktifkan Member?',
        text: activating
            ? 'Member akan dapat login dan bertransaksi kembali.'
            : 'Member tidak akan bisa login setelah dinonaktifkan.',
        icon: 'warning',
        confirmButtonText: activating ? 'Ya, Aktifkan' : 'Ya, Nonaktifkan',
        cancelButtonText: 'Batal',
    });

    if (result.isConfirmed) {
        router.patch(
            UpdateMemberStatusController(props.member.id).url,
            { is_active: activating },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.fire({
                        icon: 'success',
                        title: activating
                            ? 'Member diaktifkan.'
                            : 'Member dinonaktifkan.',
                    });
                },
            },
        );
    }
};

const onMutationsPageClick = (url: string | null) => {
    if (!url) return;
    const params = Object.fromEntries(
        new URL(url, window.location.origin).searchParams.entries(),
    );
    updateParams(params);
};

const onOrdersPageClick = (url: string | null) => {
    if (!url) return;
    const params = Object.fromEntries(
        new URL(url, window.location.origin).searchParams.entries(),
    );
    updateParams(params);
};

const onDepositsPageClick = (url: string | null) => {
    if (!url) return;
    const params = Object.fromEntries(
        new URL(url, window.location.origin).searchParams.entries(),
    );
    updateParams(params);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="title" />
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <Heading :title="member.name" :description="member.email" />
                <div class="flex items-center gap-2">
                    <Badge
                        :variant="member.is_active ? 'default' : 'destructive'"
                    >
                        {{ member.is_active ? 'Active' : 'Inactive' }}
                    </Badge>
                    <Button variant="outline" @click="toggleStatus">
                        {{ member.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <Card class="md:col-span-1">
                    <CardHeader>
                        <CardTitle>Profil</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Nama</span>
                            <span class="font-medium">{{ member.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Email</span>
                            <span class="font-medium">{{ member.email }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Phone</span>
                            <span class="font-medium">{{
                                member.phone ?? '-'
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Total Order</span
                            >
                            <span class="font-medium">{{
                                member.orders_count ?? 0
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Terdaftar</span>
                            <span class="font-medium">
                                {{
                                    dayjs(member.created_at).format(
                                        'DD MMMM YYYY',
                                    )
                                }}
                            </span>
                        </div>
                        <div class="pt-2">
                            <Button
                                variant="outline"
                                class="w-full"
                                @click="resetPassword"
                            >
                                <KeyRound class="mr-2 h-4 w-4" />
                                Reset Password
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card class="md:col-span-2">
                    <CardHeader>
                        <CardTitle>Saldo</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-muted-foreground">
                                    Saldo saat ini
                                </p>
                                <p class="text-3xl font-bold text-primary">
                                    {{ formatCurrency(member.balance) }}
                                </p>
                            </div>
                            <Dialog v-model:open="balanceDialogOpen">
                                <DialogTrigger as-child>
                                    <Button>
                                        <Wallet class="mr-2 h-4 w-4" />
                                        Sesuaikan Saldo
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <form
                                        class="space-y-4"
                                        @submit.prevent="
                                            submitBalanceAdjustment
                                        "
                                    >
                                        <DialogHeader>
                                            <DialogTitle
                                                >Sesuaikan Saldo</DialogTitle
                                            >
                                            <DialogDescription>
                                                Penambahan/pengurangan saldo
                                                akan tercatat di riwayat mutasi.
                                            </DialogDescription>
                                        </DialogHeader>

                                        <div class="grid gap-2">
                                            <Label for="type">Tipe</Label>
                                            <Select v-model="balanceForm.type">
                                                <SelectTrigger
                                                    id="type"
                                                    class="w-full"
                                                >
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="credit"
                                                        >Tambah
                                                        (Credit)</SelectItem
                                                    >
                                                    <SelectItem value="debit"
                                                        >Kurangi
                                                        (Debit)</SelectItem
                                                    >
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div class="grid gap-2">
                                            <Label for="amount">Nominal</Label>
                                            <Input
                                                id="amount"
                                                type="number"
                                                min="1"
                                                v-model.number="
                                                    balanceForm.amount
                                                "
                                            />
                                            <p
                                                v-if="balanceForm.errors.amount"
                                                class="text-sm text-destructive"
                                            >
                                                {{ balanceForm.errors.amount }}
                                            </p>
                                        </div>

                                        <div class="grid gap-2">
                                            <Label for="description"
                                                >Keterangan</Label
                                            >
                                            <Textarea
                                                id="description"
                                                v-model="
                                                    balanceForm.description
                                                "
                                                placeholder="Contoh: Kompensasi kendala teknis"
                                                rows="3"
                                            />
                                            <p
                                                v-if="
                                                    balanceForm.errors
                                                        .description
                                                "
                                                class="text-sm text-destructive"
                                            >
                                                {{
                                                    balanceForm.errors
                                                        .description
                                                }}
                                            </p>
                                        </div>

                                        <DialogFooter class="gap-2">
                                            <DialogClose as-child>
                                                <Button
                                                    type="button"
                                                    variant="secondary"
                                                    >Batal</Button
                                                >
                                            </DialogClose>
                                            <Button
                                                type="submit"
                                                :disabled="
                                                    balanceForm.processing
                                                "
                                            >
                                                Simpan
                                            </Button>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Riwayat Mutasi Saldo</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Tanggal</TableHead>
                                    <TableHead>Tipe</TableHead>
                                    <TableHead>Nominal</TableHead>
                                    <TableHead>Saldo Sebelum</TableHead>
                                    <TableHead>Saldo Sesudah</TableHead>
                                    <TableHead>Keterangan</TableHead>
                                    <TableHead>Oleh</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="mutations.data.length === 0">
                                    <TableCell
                                        colspan="7"
                                        class="h-24 text-center text-muted-foreground"
                                    >
                                        Belum ada mutasi saldo.
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-for="mutation in mutations.data"
                                    :key="mutation.id"
                                >
                                    <TableCell>
                                        {{
                                            dayjs(mutation.created_at).format(
                                                'DD MMM YYYY HH:mm',
                                            )
                                        }}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            :variant="
                                                mutation.type === 'credit'
                                                    ? 'default'
                                                    : 'destructive'
                                            "
                                        >
                                            {{
                                                mutation.type === 'credit'
                                                    ? 'Credit'
                                                    : 'Debit'
                                            }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        {{
                                            mutation.type === 'credit'
                                                ? '+'
                                                : '-'
                                        }}{{ formatCurrency(mutation.amount) }}
                                    </TableCell>
                                    <TableCell>{{
                                        formatCurrency(mutation.balance_before)
                                    }}</TableCell>
                                    <TableCell>{{
                                        formatCurrency(mutation.balance_after)
                                    }}</TableCell>
                                    <TableCell>{{
                                        mutation.description
                                    }}</TableCell>
                                    <TableCell>{{
                                        mutation.performed_by?.name ?? '-'
                                    }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">
                            Showing {{ mutations.from ?? 0 }} to
                            {{ mutations.to ?? 0 }} of
                            {{ mutations.total }} results
                        </span>
                        <div class="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="!mutations.prev_page_url"
                                @click="
                                    onMutationsPageClick(
                                        mutations.prev_page_url,
                                    )
                                "
                            >
                                Previous
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="!mutations.next_page_url"
                                @click="
                                    onMutationsPageClick(
                                        mutations.next_page_url,
                                    )
                                "
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Riwayat Order</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Referensi</TableHead>
                                    <TableHead>Brand</TableHead>
                                    <TableHead>Produk</TableHead>
                                    <TableHead>Total</TableHead>
                                    <TableHead>Tanggal</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="orders.data.length === 0">
                                    <TableCell
                                        colspan="5"
                                        class="h-24 text-center text-muted-foreground"
                                    >
                                        Belum ada order.
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-for="order in orders.data"
                                    :key="order.id"
                                >
                                    <TableCell class="font-mono">{{
                                        order.reference
                                    }}</TableCell>
                                    <TableCell>{{
                                        order.brand?.name ?? '-'
                                    }}</TableCell>
                                    <TableCell>{{
                                        order.product?.name ?? '-'
                                    }}</TableCell>
                                    <TableCell>{{
                                        formatCurrency(order.total_amount)
                                    }}</TableCell>
                                    <TableCell>
                                        {{
                                            dayjs(order.created_at).format(
                                                'DD MMM YYYY HH:mm',
                                            )
                                        }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">
                            Showing {{ orders.from ?? 0 }} to
                            {{ orders.to ?? 0 }} of {{ orders.total }} results
                        </span>
                        <div class="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="!orders.prev_page_url"
                                @click="onOrdersPageClick(orders.prev_page_url)"
                            >
                                Previous
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="!orders.next_page_url"
                                @click="onOrdersPageClick(orders.next_page_url)"
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Riwayat Deposit</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Reference</TableHead>
                                    <TableHead>Nominal</TableHead>
                                    <TableHead>Total Bayar</TableHead>
                                    <TableHead>Channel</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Tanggal</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="deposits.data.length === 0">
                                    <TableCell
                                        colspan="6"
                                        class="h-24 text-center text-muted-foreground"
                                    >
                                        Belum ada deposit.
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-for="deposit in deposits.data"
                                    :key="deposit.id"
                                >
                                    <TableCell class="font-mono">
                                        {{ deposit.reference }}
                                    </TableCell>
                                    <TableCell>
                                        {{ formatCurrency(deposit.amount) }}
                                    </TableCell>
                                    <TableCell>
                                        {{ formatCurrency(deposit.total_pay) }}
                                    </TableCell>
                                    <TableCell class="uppercase">
                                        {{ deposit.channel }}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            :variant="
                                                deposit.status === 'paid'
                                                    ? 'default'
                                                    : deposit.status ===
                                                        'pending'
                                                      ? 'secondary'
                                                      : 'destructive'
                                            "
                                        >
                                            {{ deposit.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        {{
                                            dayjs(deposit.created_at).format(
                                                'DD MMM YYYY HH:mm',
                                            )
                                        }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">
                            Showing {{ deposits.from ?? 0 }} to
                            {{ deposits.to ?? 0 }} of {{ deposits.total }}
                            results
                        </span>
                        <div class="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="!deposits.prev_page_url"
                                @click="
                                    onDepositsPageClick(deposits.prev_page_url)
                                "
                            >
                                Previous
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="!deposits.next_page_url"
                                @click="
                                    onDepositsPageClick(deposits.next_page_url)
                                "
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
