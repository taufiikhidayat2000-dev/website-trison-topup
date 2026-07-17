<script setup lang="ts">
import ApproveResellerApplicationController from '@/actions/App/Http/Controllers/Cms/Reseller/ApproveResellerApplicationController';
import RejectResellerApplicationController from '@/actions/App/Http/Controllers/Cms/Reseller/RejectResellerApplicationController';
import Heading from '@/components/Heading.vue';
import ResourceTable from '@/components/ResourceTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useFilter } from '@/composables/useFilter';
import { useSwal } from '@/composables/useSwal';
import AppLayout from '@/layouts/AppLayout.vue';
import { PaginationItem, type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { ref } from 'vue';

interface ResellerApplicationItem {
    id: number;
    business_name: string;
    note: string | null;
    status: 'pending' | 'approved' | 'rejected';
    rejection_reason: string | null;
    created_at: string;
    user: { id: number; name: string; email: string } | null;
    reviewed_by: { id: number; name: string } | null;
}

defineProps<{
    data: PaginationItem<ResellerApplicationItem>;
    orderBy?: string;
    order?: 'asc' | 'desc';
    search?: string;
    paginate?: number;
    status?: string;
}>();

const title = 'Pengajuan Reseller';
const description =
    'Tinjau, setujui, atau tolak pengajuan reseller dari member.';

const columns = [
    { label: 'Usaha', key: 'business_name', sortable: true },
    { label: 'Pemohon', key: 'user', sortable: false },
    { label: 'Catatan', key: 'note', sortable: false },
    { label: 'Status', key: 'status', sortable: false },
    { label: 'Tanggal', key: 'created_at', sortable: true },
    {
        label: 'Actions',
        key: 'actions',
        sortable: false,
        class: 'w-40 text-center',
    },
];

const breadcrumbItems: BreadcrumbItem[] = [{ title: title, href: '#' }];

const { updateParams } = useFilter();
const { confirm, toast } = useSwal();

const statusFilter = (value: string) => {
    updateParams({ status: value === 'all' ? '' : value, page: 1 });
};

const approve = async (application: ResellerApplicationItem) => {
    const result = await confirm({
        title: 'Setujui Pengajuan Reseller?',
        text: `${application.user?.name} akan langsung mendapat role reseller dan harga khusus.`,
        icon: 'question',
        confirmButtonText: 'Ya, Setujui',
        cancelButtonText: 'Batal',
    });

    if (result.isConfirmed) {
        router.patch(
            ApproveResellerApplicationController(application.id).url,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.fire({
                        icon: 'success',
                        title: 'Pengajuan reseller disetujui.',
                    });
                },
            },
        );
    }
};

// Reject dialog
const rejectDialogOpenId = ref<number | null>(null);
const rejectForm = useForm({
    reason: '',
});

const submitReject = (application: ResellerApplicationItem) => {
    rejectForm.patch(RejectResellerApplicationController(application.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            rejectDialogOpenId.value = null;
            rejectForm.reset();
            toast.fire({
                icon: 'success',
                title: 'Pengajuan reseller ditolak.',
            });
        },
    });
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
                    @update:model-value="(v) => statusFilter(String(v))"
                >
                    <SelectTrigger class="w-44">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua Status</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="approved">Approved</SelectItem>
                        <SelectItem value="rejected">Rejected</SelectItem>
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
                <template #business_name="{ row }">
                    {{ row.business_name }}
                </template>
                <template #user="{ row }">
                    <div v-if="row.user">
                        <div class="font-medium">{{ row.user.name }}</div>
                        <div class="text-xs text-muted-foreground">
                            {{ row.user.email }}
                        </div>
                    </div>
                    <span v-else>-</span>
                </template>
                <template #note="{ row }">
                    <span class="line-clamp-2 max-w-xs text-sm">
                        {{ row.note ?? '-' }}
                    </span>
                </template>
                <template #status="{ row }">
                    <Badge
                        :variant="
                            row.status === 'approved'
                                ? 'default'
                                : row.status === 'rejected'
                                  ? 'destructive'
                                  : 'secondary'
                        "
                    >
                        {{ row.status }}
                    </Badge>
                    <p
                        v-if="row.status === 'rejected' && row.rejection_reason"
                        class="mt-1 text-xs text-muted-foreground"
                    >
                        {{ row.rejection_reason }}
                    </p>
                </template>
                <template #created_at="{ row }">
                    {{ dayjs(row.created_at).format('DD MMM YYYY HH:mm') }}
                </template>
                <template #actions="{ row }">
                    <div
                        v-if="row.status === 'pending'"
                        class="flex items-center justify-center gap-2"
                    >
                        <Button size="sm" @click="approve(row)">
                            Setujui
                        </Button>

                        <Dialog
                            :open="rejectDialogOpenId === row.id"
                            @update:open="
                                (v) => (rejectDialogOpenId = v ? row.id : null)
                            "
                        >
                            <DialogTrigger as-child>
                                <Button size="sm" variant="destructive">
                                    Tolak
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <form
                                    class="space-y-4"
                                    @submit.prevent="submitReject(row)"
                                >
                                    <DialogHeader>
                                        <DialogTitle
                                            >Tolak Pengajuan
                                            Reseller</DialogTitle
                                        >
                                        <DialogDescription>
                                            Berikan alasan penolakan untuk
                                            {{ row.user?.name }}.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <div class="grid gap-2">
                                        <Textarea
                                            v-model="rejectForm.reason"
                                            rows="3"
                                            placeholder="Contoh: Data usaha belum lengkap"
                                        />
                                        <p
                                            v-if="rejectForm.errors.reason"
                                            class="text-sm text-destructive"
                                        >
                                            {{ rejectForm.errors.reason }}
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
                                            variant="destructive"
                                            :disabled="rejectForm.processing"
                                        >
                                            Tolak Pengajuan
                                        </Button>
                                    </DialogFooter>
                                </form>
                            </DialogContent>
                        </Dialog>
                    </div>
                    <span v-else class="text-xs text-muted-foreground">
                        {{
                            row.reviewed_by
                                ? `oleh ${row.reviewed_by.name}`
                                : '-'
                        }}
                    </span>
                </template>
            </ResourceTable>
        </div>
    </AppLayout>
</template>
