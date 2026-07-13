<script setup lang="ts">
import { update } from '@/actions/App/Http/Controllers/Cms/Marketing/FlashSaleController';
import ImageUploadPreview from '@/components/ImageUploadPreview.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import MoneyInput from '@/components/ui/money-input/MoneyInput.vue';
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
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import useFlashSaleProductQuery from '@/composables/query/useFlashSaleProductQuery';
import { useSwal } from '@/composables/useSwal';
import { formatCurrency } from '@/lib/utils';
import { Form, router } from '@inertiajs/vue3';
import { Modal } from '@inertiaui/modal-vue';
import { Plus, Save, Trash2, X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    flashSale: any;
}>();

const { toast, confirm } = useSwal();
const { searchProducts } = useFlashSaleProductQuery();

const activeTab = ref<'detail' | 'products'>('detail');
const iconType = ref<string>(props.flashSale.icon_type ?? 'emoji');

// --- Product picker ---
const pickerOpen = ref(false);
const search = ref('');
const selectedProductIds = ref<number[]>([]);
const pricingType = ref<'percent' | 'manual'>('percent');
const discountPercent = ref<number | null>(20);
const flashPrice = ref<number | null>(null);
const flashStock = ref<number | null>(10);
const attaching = ref(false);

const { data: searchResults, isLoading: searching } = searchProducts(
    props.flashSale.id,
    search,
    ref(null),
    ref(null),
);

const toggleProduct = (id: number) => {
    const idx = selectedProductIds.value.indexOf(id);
    if (idx === -1) {
        selectedProductIds.value.push(id);
    } else {
        selectedProductIds.value.splice(idx, 1);
    }
};

const submitAttach = () => {
    if (selectedProductIds.value.length === 0) {
        toast.fire({ icon: 'error', title: 'Pilih minimal satu produk.' });
        return;
    }

    attaching.value = true;

    router.post(
        `/cms/marketing/flash-sales/${props.flashSale.id}/products`,
        {
            product_ids: selectedProductIds.value,
            pricing_type: pricingType.value,
            discount_percent: discountPercent.value,
            flash_price: flashPrice.value,
            flash_stock: flashStock.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.fire({ icon: 'success', title: 'Produk berhasil ditambahkan.' });
                selectedProductIds.value = [];
                pickerOpen.value = false;
                router.reload({ only: ['flashSale'] });
            },
            onError: () => {
                toast.fire({ icon: 'error', title: 'Gagal menambahkan produk.' });
            },
            onFinish: () => {
                attaching.value = false;
            },
        },
    );
};

// --- Attached products table ---
const editingRow = ref<any | null>(null);
const editPricingType = ref<'percent' | 'manual'>('percent');
const editDiscountPercent = ref<number | null>(null);
const editFlashPrice = ref<number | null>(null);
const editFlashStock = ref<number | null>(null);
const editStatus = ref<string>('active');

const openEditRow = (row: any) => {
    editingRow.value = row;
    editPricingType.value = row.pricing_type;
    editDiscountPercent.value = row.discount_percent;
    editFlashPrice.value = row.flash_price;
    editFlashStock.value = row.flash_stock;
    editStatus.value = row.status;
};

const submitEditRow = () => {
    if (!editingRow.value) return;

    router.patch(
        `/cms/marketing/flash-sales/${props.flashSale.id}/products/${editingRow.value.id}`,
        {
            pricing_type: editPricingType.value,
            discount_percent: editDiscountPercent.value,
            flash_price: editFlashPrice.value,
            flash_stock: editFlashStock.value,
            status: editStatus.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.fire({ icon: 'success', title: 'Produk berhasil diperbarui.' });
                editingRow.value = null;
                router.reload({ only: ['flashSale'] });
            },
        },
    );
};

const removeProduct = (row: any) => {
    confirm({
        title: 'Hapus Produk',
        text: 'Produk ini akan dihapus dari Flash Sale.',
        icon: 'warning',
        confirmButtonText: 'Ya, hapus!',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(
                `/cms/marketing/flash-sales/${props.flashSale.id}/products/${row.id}`,
                {
                    preserveScroll: true,
                    onSuccess: () => router.reload({ only: ['flashSale'] }),
                },
            );
        }
    });
};

const progressPercent = (row: any) =>
    row.flash_stock > 0 ? Math.round((row.sold / row.flash_stock) * 100) : 0;
</script>

<template>
    <Modal v-slot="{ close }">
        <div class="p-6">
            <h2 class="text-lg font-medium">Edit Flash Sale</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ flashSale.title }}
            </p>

            <div class="mt-4 flex gap-2">
                <button
                    type="button"
                    class="rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors"
                    :class="
                        activeTab === 'detail'
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border/50 text-muted-foreground hover:border-primary/50'
                    "
                    @click="activeTab = 'detail'"
                >
                    Detail
                </button>
                <button
                    type="button"
                    class="rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors"
                    :class="
                        activeTab === 'products'
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border/50 text-muted-foreground hover:border-primary/50'
                    "
                    @click="activeTab = 'products'"
                >
                    Produk Flash Sale ({{ flashSale.products?.length ?? 0 }})
                </button>
            </div>

            <!-- Detail tab -->
            <Form
                v-if="activeTab === 'detail'"
                v-bind="update.form({ flash_sale: flashSale.id })"
                class="mt-6 space-y-6"
                @success="
                    () => {
                        toast.fire({
                            icon: 'success',
                            title: 'Flash Sale berhasil diperbarui.',
                        });
                        close();
                    }
                "
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="title">Judul Flash Sale</Label>
                    <Input
                        id="title"
                        name="title"
                        :default-value="flashSale.title"
                        required
                        autofocus
                    />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="subtitle">Sub Judul (Opsional)</Label>
                    <Input
                        id="subtitle"
                        name="subtitle"
                        :default-value="flashSale.subtitle"
                    />
                    <InputError :message="errors.subtitle" />
                </div>

                <div class="grid gap-2">
                    <Label for="icon_type">Icon</Label>
                    <input type="hidden" name="icon_type" :value="iconType" />
                    <Select
                        :default-value="iconType"
                        @update:model-value="(val) => (iconType = val as string)"
                    >
                        <SelectTrigger id="icon_type" class="w-full">
                            <SelectValue placeholder="Pilih jenis icon" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="emoji">Emoji</SelectItem>
                            <SelectItem value="image">Upload Gambar</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.icon_type" />
                </div>

                <div v-if="iconType === 'emoji'" class="grid gap-2">
                    <Label for="icon_emoji">Emoji</Label>
                    <Input
                        id="icon_emoji"
                        name="icon_emoji"
                        :default-value="flashSale.icon_emoji"
                        maxlength="8"
                    />
                    <InputError :message="errors.icon_emoji" />
                </div>

                <div v-else class="grid gap-2">
                    <Label for="icon_image">Gambar Icon</Label>
                    <img
                        v-if="flashSale.icon_image_url"
                        :src="flashSale.icon_image_url"
                        class="h-12 w-12 rounded object-contain"
                    />
                    <ImageUploadPreview
                        input-id="icon_image"
                        input-name="icon_image"
                        label=""
                        description="Upload icon baru untuk mengganti (Max 2MB). Ukuran disarankan 256x256 px (persegi)."
                        accept="image/*"
                        :max-size="2"
                        preview-height="120px"
                        :errors="errors.icon_image"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="banner">Banner (Opsional)</Label>
                    <img
                        v-if="flashSale.banner_url"
                        :src="flashSale.banner_url"
                        class="h-24 w-full rounded object-cover"
                    />
                    <ImageUploadPreview
                        input-id="banner"
                        input-name="banner"
                        label=""
                        description="Upload banner baru untuk mengganti (Max 4MB). Ukuran disarankan 1200x400 px (rasio 3:1)."
                        accept="image/*"
                        :max-size="4"
                        preview-height="160px"
                        :errors="errors.banner"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="start_time">Mulai</Label>
                        <Input
                            id="start_time"
                            name="start_time"
                            type="datetime-local"
                            :default-value="
                                flashSale.start_time
                                    ? new Date(flashSale.start_time).toISOString().slice(0, 16)
                                    : ''
                            "
                            required
                        />
                        <InputError :message="errors.start_time" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="end_time">Berakhir</Label>
                        <Input
                            id="end_time"
                            name="end_time"
                            type="datetime-local"
                            :default-value="
                                flashSale.end_time
                                    ? new Date(flashSale.end_time).toISOString().slice(0, 16)
                                    : ''
                            "
                            required
                        />
                        <InputError :message="errors.end_time" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="countdown_style">Countdown</Label>
                    <Select name="countdown_style" :default-value="flashSale.countdown_style">
                        <SelectTrigger id="countdown_style" class="w-full">
                            <SelectValue placeholder="Pilih gaya countdown" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="detailed">Hari Jam Menit Detik</SelectItem>
                            <SelectItem value="compact">Compact</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.countdown_style" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="auto_start">Auto Start</Label>
                        <Select
                            name="auto_start"
                            :default-value="flashSale.auto_start ? '1' : '0'"
                        >
                            <SelectTrigger id="auto_start" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="1">Aktifkan Otomatis</SelectItem>
                                <SelectItem value="0">Manual</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.auto_start" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="auto_end">Auto End</Label>
                        <Select
                            name="auto_end"
                            :default-value="flashSale.auto_end ? '1' : '0'"
                        >
                            <SelectTrigger id="auto_end" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="1">Berakhir Otomatis</SelectItem>
                                <SelectItem value="0">Manual</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.auto_end" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="after_end_action">Setelah Flash Sale Berakhir</Label>
                    <Select name="after_end_action" :default-value="flashSale.after_end_action">
                        <SelectTrigger id="after_end_action" class="w-full">
                            <SelectValue placeholder="Pilih aksi" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="revert_price">Kembali ke Harga Normal</SelectItem>
                            <SelectItem value="hide">Sembunyikan Flash Sale</SelectItem>
                            <SelectItem value="sold_out">Tampilkan Sold Out</SelectItem>
                            <SelectItem value="keep_showing">Tetap Tampil</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.after_end_action" />
                </div>

                <div class="grid gap-2">
                    <Label for="status">Status</Label>
                    <Select name="status" :default-value="flashSale.status">
                        <SelectTrigger id="status" class="w-full">
                            <SelectValue placeholder="Pilih status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="draft">Draft</SelectItem>
                            <SelectItem value="scheduled">Scheduled</SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="ended">Ended</SelectItem>
                            <SelectItem value="disabled">Disabled</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.status" />
                </div>

                <div class="flex justify-end gap-4">
                    <Button :disabled="processing" type="submit">
                        <Save class="mr-2 h-4 w-4" />
                        Simpan
                    </Button>
                </div>
            </Form>

            <!-- Products tab -->
            <div v-else class="mt-6 space-y-4">
                <div class="flex justify-end">
                    <Button variant="outline" @click="pickerOpen = true">
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah Produk
                    </Button>
                </div>

                <!--
                    Plain overlay instead of the ui/dialog (reka-ui) component: reka-ui's
                    Dialog traps focus internally, which fights the InertiaUI <Modal> this
                    is nested inside and blocks keyboard input in the fields below.
                -->
                <div
                    v-if="pickerOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
                    @click.self="pickerOpen = false"
                >
                    <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg border bg-background p-6 shadow-lg">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-semibold">Tambah Produk ke Flash Sale</h3>
                            <button
                                type="button"
                                class="text-muted-foreground hover:text-foreground"
                                @click="pickerOpen = false"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="space-y-4">
                            <Input
                                v-model="search"
                                placeholder="Cari produk..."
                            />

                            <div class="max-h-64 space-y-1 overflow-y-auto rounded-md border p-2">
                                <div v-if="searching" class="p-2 text-sm text-muted-foreground">
                                    Mencari...
                                </div>
                                <div
                                    v-for="product in searchResults ?? []"
                                    :key="product.id"
                                    class="flex items-center gap-3 rounded p-2 hover:bg-muted/50"
                                    :class="{ 'opacity-50': product.already_in_conflicting_sale }"
                                >
                                    <Checkbox
                                        :model-value="selectedProductIds.includes(product.id)"
                                        :disabled="product.already_in_conflicting_sale"
                                        @update:model-value="() => toggleProduct(product.id)"
                                    />
                                    <div class="flex-1 text-sm">
                                        <div class="font-medium">{{ product.name }}</div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ product.brand?.name }} · {{ formatCurrency(product.sell_price) }}
                                            <span v-if="product.already_in_conflicting_sale" class="text-destructive">
                                                · Sudah ada di Flash Sale lain
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-if="!searching && (searchResults ?? []).length === 0"
                                    class="p-2 text-sm text-muted-foreground"
                                >
                                    Tidak ada produk ditemukan.
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label>Pengaturan Harga</Label>
                                <Select v-model="pricingType">
                                    <SelectTrigger class="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="percent">Diskon Persen</SelectItem>
                                        <SelectItem value="manual">Harga Manual</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div v-if="pricingType === 'percent'" class="grid gap-2">
                                <Label>Diskon (%)</Label>
                                <Input v-model.number="discountPercent" type="number" min="0" max="100" />
                            </div>
                            <div v-else class="grid gap-2">
                                <Label>Harga Flash Sale</Label>
                                <MoneyInput v-model="flashPrice" />
                            </div>

                            <div class="grid gap-2">
                                <Label>Flash Stock</Label>
                                <Input v-model.number="flashStock" type="number" min="1" />
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <Button :disabled="attaching" @click="submitAttach">
                                Tambah ({{ selectedProductIds.length }})
                            </Button>
                        </div>
                    </div>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Produk</TableHead>
                            <TableHead>Harga Normal</TableHead>
                            <TableHead>Harga Flash</TableHead>
                            <TableHead>Diskon</TableHead>
                            <TableHead>Progress</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="w-20 text-center">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty v-if="(flashSale.products ?? []).length === 0" :colspan="7">
                            Belum ada produk di Flash Sale ini.
                        </TableEmpty>
                        <TableRow v-for="row in flashSale.products ?? []" :key="row.id">
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <img
                                        v-if="row.product?.image"
                                        :src="row.product.image"
                                        class="h-8 w-8 rounded object-cover"
                                    />
                                    <span class="text-sm">{{ row.product?.name }}</span>
                                </div>
                            </TableCell>
                            <TableCell>{{ formatCurrency(row.product?.sell_price ?? 0) }}</TableCell>
                            <TableCell class="font-semibold text-primary">
                                {{ formatCurrency(row.flash_price) }}
                            </TableCell>
                            <TableCell>
                                <span v-if="row.discount_percent">{{ row.discount_percent }}%</span>
                                <span v-else>-</span>
                            </TableCell>
                            <TableCell>
                                <div class="w-32">
                                    <div class="h-2 w-full overflow-hidden rounded-full bg-secondary">
                                        <div
                                            class="h-full bg-primary"
                                            :style="{ width: progressPercent(row) + '%' }"
                                        ></div>
                                    </div>
                                    <div class="mt-1 text-xs text-muted-foreground">
                                        {{ row.sold }} / {{ row.flash_stock }} terjual
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <span
                                    class="rounded-full px-2 py-1 text-xs font-semibold"
                                    :class="
                                        row.status === 'sold_out'
                                            ? 'bg-red-100 text-red-800'
                                            : 'bg-green-100 text-green-800'
                                    "
                                >
                                    {{ row.status }}
                                </span>
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center justify-center gap-1">
                                    <Button variant="ghost" size="icon" @click="openEditRow(row)">
                                        <Save class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="removeProduct(row)">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <div
                    v-if="editingRow"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
                    @click.self="editingRow = null"
                >
                    <div class="w-full max-w-lg rounded-lg border bg-background p-6 shadow-lg">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-semibold">Edit Harga & Stock</h3>
                            <button
                                type="button"
                                class="text-muted-foreground hover:text-foreground"
                                @click="editingRow = null"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                        <div class="space-y-4">
                            <div class="grid gap-2">
                                <Label>Pengaturan Harga</Label>
                                <Select v-model="editPricingType">
                                    <SelectTrigger class="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="percent">Diskon Persen</SelectItem>
                                        <SelectItem value="manual">Harga Manual</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div v-if="editPricingType === 'percent'" class="grid gap-2">
                                <Label>Diskon (%)</Label>
                                <Input v-model.number="editDiscountPercent" type="number" min="0" max="100" />
                            </div>
                            <div v-else class="grid gap-2">
                                <Label>Harga Flash Sale</Label>
                                <MoneyInput v-model="editFlashPrice" />
                            </div>
                            <div class="grid gap-2">
                                <Label>Flash Stock</Label>
                                <Input v-model.number="editFlashStock" type="number" min="1" />
                            </div>
                            <div class="grid gap-2">
                                <Label>Status</Label>
                                <Select v-model="editStatus">
                                    <SelectTrigger class="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="sold_out">Sold Out</SelectItem>
                                        <SelectItem value="inactive">Inactive</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end">
                            <Button @click="submitEditRow">Simpan</Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>
