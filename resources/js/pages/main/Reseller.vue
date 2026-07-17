<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/Main/ResellerController';
import MainFooter from '@/components/MainFooter.vue';
import MainHeader from '@/components/MainHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useSwal } from '@/composables/useSwal';
import { formatCurrency } from '@/lib/utils';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { BadgeCheck, Handshake, ShieldCheck, Wallet } from 'lucide-vue-next';

interface ResellerApplication {
    id: number;
    business_name: string;
    note: string | null;
    status: 'pending' | 'approved' | 'rejected';
    rejection_reason: string | null;
    created_at: string;
}

const props = defineProps<{
    isReseller: boolean;
    latestApplication: ResellerApplication | null;
    resellerDiscountPercent: number;
}>();

const page = usePage();
const user = page.props.auth.user;
const { toast } = useSwal();

const form = useForm({
    business_name: '',
    note: '',
});

const canApply =
    !props.isReseller &&
    (!props.latestApplication || props.latestApplication.status !== 'pending');

const submit = () => {
    form.post(store().url, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            toast.fire({
                icon: 'success',
                title: 'Pengajuan reseller berhasil dikirim.',
            });
        },
    });
};
</script>

<template>
    <Head title="Reseller" />

    <div class="flex min-h-screen flex-col bg-background">
        <MainHeader />

        <main class="flex-1">
            <div class="mx-auto max-w-3xl px-4 py-8">
                <div class="mb-6 flex items-center gap-3">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-full bg-primary/10 text-primary"
                    >
                        <Handshake class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-foreground">
                            Program Reseller
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            Dapatkan harga khusus
                            {{ resellerDiscountPercent }}% lebih murah untuk
                            setiap transaksi.
                        </p>
                    </div>
                </div>

                <!-- Already a reseller -->
                <div
                    v-if="isReseller"
                    class="rounded-lg border border-border/50 bg-card p-6 shadow-sm"
                >
                    <div class="flex items-center gap-2">
                        <Badge variant="default">
                            <ShieldCheck class="mr-1 h-3.5 w-3.5" />
                            Reseller Aktif
                        </Badge>
                    </div>
                    <p class="mt-3 text-sm text-muted-foreground">
                        Akun Anda sudah menjadi reseller. Setiap pembelian
                        produk otomatis mendapat harga
                        {{ resellerDiscountPercent }}% lebih murah dari harga
                        normal, selama tidak sedang ada Flash Sale pada produk
                        tersebut.
                    </p>
                    <div
                        class="mt-4 flex items-center justify-between rounded-lg bg-muted/50 p-4"
                    >
                        <div class="flex items-center gap-2 text-sm">
                            <Wallet class="h-4 w-4 text-muted-foreground" />
                            <span class="text-muted-foreground"
                                >Saldo Anda</span
                            >
                        </div>
                        <span class="text-lg font-bold text-primary">
                            {{ formatCurrency(user?.balance ?? 0) }}
                        </span>
                    </div>
                    <p class="mt-3 text-xs text-muted-foreground">
                        Harga reseller berlaku untuk semua metode pembayaran
                        (saldo, transfer manual, maupun otomatis). Top up saldo
                        lewat halaman Profile kalau ingin bayar pakai saldo.
                    </p>
                </div>

                <!-- Pending application -->
                <div
                    v-else-if="latestApplication?.status === 'pending'"
                    class="rounded-lg border border-border/50 bg-card p-6 shadow-sm"
                >
                    <Badge variant="secondary">Menunggu Persetujuan</Badge>
                    <p class="mt-3 text-sm text-muted-foreground">
                        Pengajuan reseller atas nama
                        <span class="font-medium text-foreground">{{
                            latestApplication.business_name
                        }}</span>
                        sedang ditinjau oleh admin. Anda akan mendapat harga
                        reseller otomatis begitu pengajuan disetujui.
                    </p>
                </div>

                <!-- Form (new application or re-apply after rejection) -->
                <div v-if="canApply" class="space-y-4">
                    <div
                        v-if="latestApplication?.status === 'rejected'"
                        class="rounded-lg border border-destructive/50 bg-destructive/5 p-4 text-sm"
                    >
                        <p class="font-medium text-destructive">
                            Pengajuan sebelumnya ditolak.
                        </p>
                        <p
                            v-if="latestApplication.rejection_reason"
                            class="mt-1 text-muted-foreground"
                        >
                            Alasan: {{ latestApplication.rejection_reason }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            Anda dapat mengajukan kembali di bawah ini.
                        </p>
                    </div>

                    <form
                        class="space-y-4 rounded-lg border border-border/50 bg-card p-6 shadow-sm"
                        @submit.prevent="submit"
                    >
                        <div class="grid gap-2">
                            <Label for="business_name">Nama Usaha</Label>
                            <Input
                                id="business_name"
                                v-model="form.business_name"
                                placeholder="Contoh: Konter Pulsa Barokah"
                            />
                            <p
                                v-if="form.errors.business_name"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.business_name }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="note">Catatan (opsional)</Label>
                            <Textarea
                                id="note"
                                v-model="form.note"
                                rows="3"
                                placeholder="Ceritakan sedikit tentang usaha Anda, perkiraan volume transaksi, dsb."
                            />
                            <p
                                v-if="form.errors.note"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.note }}
                            </p>
                        </div>

                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="form.processing"
                        >
                            <BadgeCheck class="mr-2 h-4 w-4" />
                            Ajukan jadi Reseller
                        </Button>
                    </form>
                </div>
            </div>
        </main>

        <MainFooter />
    </div>
</template>
