<script setup lang="ts">
import {
    importMethod as importProducts,
    importTemplate,
} from '@/actions/App/Http/Controllers/Cms/PPOB/PPOBProductController';
import InputDescription from '@/components/InputDescription.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useSwal } from '@/composables/useSwal';
import { useForm, usePage } from '@inertiajs/vue3';
import { Modal } from '@inertiaui/modal-vue';
import { AlertTriangle, CheckCircle2, Download, Upload } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const { toast } = useSwal();
const page = usePage();

const fileInput = ref<HTMLInputElement | null>(null);
const imagesInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    file: null as File | null,
    images: [] as File[],
});

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.file = target.files[0];
    }
};

const handleImagesChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    form.images = target.files ? Array.from(target.files) : [];
};

const importResult = computed(
    () =>
        page.props.importResult as {
            created: number;
            updated: number;
            errors: string[];
        } | null,
);

const submit = () => {
    if (!form.file) {
        toast.fire({
            icon: 'error',
            title: 'Pilih file Excel terlebih dahulu.',
        });
        return;
    }

    form.post(importProducts().url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            if (fileInput.value) fileInput.value.value = '';
            if (imagesInput.value) imagesInput.value.value = '';
            toast.fire({ icon: 'success', title: 'Import selesai diproses.' });
        },
    });
};
</script>

<template>
    <Modal v-slot="{ close }">
        <div class="p-6">
            <h2 class="text-lg font-medium">Import Produk dari Excel</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Isi produk lewat file Excel supaya tidak perlu tambah satu-satu
                secara manual. Produk yang SKU-nya sudah ada akan diperbarui,
                yang belum ada akan otomatis dibuat baru dan langsung tampil di
                halaman depan.
            </p>

            <a
                :href="importTemplate().url"
                class="mt-4 flex items-center gap-2 rounded-lg border border-dashed border-primary/50 bg-primary/5 p-3 text-sm font-medium text-primary hover:bg-primary/10"
            >
                <Download class="h-4 w-4" />
                Download Template Excel
            </a>

            <div class="mt-6 grid gap-2">
                <Label for="file">File Excel (.xlsx)</Label>
                <InputDescription>
                    Isi template sesuai contoh, lalu upload di sini. Kolom
                    Category dan Brand harus persis sama dengan yang sudah ada
                    di sistem.
                </InputDescription>
                <input
                    id="file"
                    ref="fileInput"
                    type="file"
                    accept=".xlsx,.xls"
                    class="block w-full rounded-lg border border-border bg-background p-2 text-sm"
                    @change="handleFileChange"
                />
                <p v-if="form.errors.file" class="text-sm text-destructive">
                    {{ form.errors.file }}
                </p>
            </div>

            <div class="mt-4 grid gap-2">
                <Label for="images">Foto Produk (opsional)</Label>
                <InputDescription>
                    Pilih beberapa foto sekaligus dari komputer Anda. Nama file
                    harus sama dengan SKU produk, contoh: file
                    <code>ML86.jpg</code> otomatis jadi gambar produk dengan SKU
                    <code>ML86</code>. Kalau satu foto dipakai untuk beberapa
                    SKU sekaligus, gabungkan SKU-nya dengan tanda "+" di nama
                    file, contoh: <code>ML86+ML172+ML257.jpg</code>. Kalau
                    tidak ada foto lokal yang cocok, sistem akan pakai kolom
                    "Image URL" di Excel (kalau diisi) sebagai cadangan.
                </InputDescription>
                <input
                    id="images"
                    ref="imagesInput"
                    type="file"
                    accept="image/*"
                    multiple
                    class="block w-full rounded-lg border border-border bg-background p-2 text-sm"
                    @change="handleImagesChange"
                />
                <p
                    v-if="form.images.length > 0"
                    class="text-xs text-muted-foreground"
                >
                    {{ form.images.length }} foto dipilih.
                </p>
                <p v-if="form.errors.images" class="text-sm text-destructive">
                    {{ form.errors.images }}
                </p>
            </div>

            <div
                v-if="importResult"
                class="mt-6 space-y-3 rounded-lg border p-4"
            >
                <div class="flex items-center gap-2 text-sm">
                    <CheckCircle2 class="h-4 w-4 text-green-600" />
                    <span
                        ><strong>{{ importResult.created }}</strong> produk baru
                        dibuat,
                        <strong>{{ importResult.updated }}</strong> produk
                        diperbarui.</span
                    >
                </div>
                <div v-if="importResult.errors.length > 0" class="space-y-1">
                    <div
                        class="flex items-center gap-2 text-sm font-medium text-destructive"
                    >
                        <AlertTriangle class="h-4 w-4" />
                        {{ importResult.errors.length }} baris bermasalah:
                    </div>
                    <ul
                        class="max-h-40 list-disc space-y-1 overflow-y-auto pl-6 text-xs text-muted-foreground"
                    >
                        <li v-for="(error, i) in importResult.errors" :key="i">
                            {{ error }}
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-4">
                <Button variant="outline" @click="close">Tutup</Button>
                <Button :disabled="form.processing" @click="submit">
                    <Upload class="mr-2 h-4 w-4" />
                    Import
                </Button>
            </div>
        </div>
    </Modal>
</template>
