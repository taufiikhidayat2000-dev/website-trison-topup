<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/Cms/Marketing/FlashSaleController';
import ImageUploadPreview from '@/components/ImageUploadPreview.vue';
import InputDescription from '@/components/InputDescription.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useSwal } from '@/composables/useSwal';
import { Form } from '@inertiajs/vue3';
import { Modal } from '@inertiaui/modal-vue';
import { Save } from 'lucide-vue-next';
import { ref } from 'vue';

const { toast } = useSwal();

const iconType = ref<string>('emoji');
</script>

<template>
    <Modal v-slot="{ close }">
        <div class="p-6">
            <h2 class="text-lg font-medium">Buat Flash Sale</h2>

            <p class="mt-1 text-sm text-muted-foreground">
                Isi informasi Flash Sale di bawah ini. Produk bisa ditambahkan
                setelah Flash Sale dibuat.
            </p>

            <Form
                v-bind="store.form()"
                class="mt-6 space-y-6"
                @success="
                    () => {
                        toast.fire({
                            icon: 'success',
                            title: 'Flash Sale berhasil dibuat.',
                        });
                        close();
                    }
                "
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="title">Judul Flash Sale</Label>
                    <InputDescription>
                        Contoh: Flash Sale Weekend, Midnight Sale, 11.11 Flash Sale.
                    </InputDescription>
                    <Input
                        id="title"
                        name="title"
                        placeholder="Flash Sale Weekend"
                        required
                        autofocus
                    />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="subtitle">Sub Judul (Opsional)</Label>
                    <InputDescription>
                        Contoh: Diskon Terbesar Hari Ini, Hanya Sampai Tengah Malam.
                    </InputDescription>
                    <Input
                        id="subtitle"
                        name="subtitle"
                        placeholder="Diskon Terbesar Hari Ini"
                    />
                    <InputError :message="errors.subtitle" />
                </div>

                <div class="grid gap-2">
                    <Label for="icon_type">Icon</Label>
                    <InputDescription>
                        Pakai emoji atau upload gambar sebagai icon Flash Sale.
                    </InputDescription>
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
                        placeholder="🔥"
                        maxlength="8"
                    />
                    <InputError :message="errors.icon_emoji" />
                </div>

                <div v-else class="grid gap-2">
                    <Label for="icon_image">Gambar Icon</Label>
                    <ImageUploadPreview
                        input-id="icon_image"
                        input-name="icon_image"
                        label=""
                        description="Upload icon Flash Sale (Max 2MB). Ukuran disarankan 256x256 px (persegi)."
                        accept="image/*"
                        :max-size="2"
                        preview-height="120px"
                        :errors="errors.icon_image"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="banner">Banner (Opsional)</Label>
                    <InputDescription>
                        Banner khusus untuk Flash Sale ini.
                    </InputDescription>
                    <ImageUploadPreview
                        input-id="banner"
                        input-name="banner"
                        label=""
                        description="Upload banner Flash Sale (Max 4MB). Ukuran disarankan 1200x400 px (rasio 3:1)."
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
                            required
                        />
                        <InputError :message="errors.end_time" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="countdown_style">Countdown</Label>
                    <Select name="countdown_style" default-value="detailed">
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
                        <Select name="auto_start" default-value="1">
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
                        <Select name="auto_end" default-value="1">
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
                    <Select name="after_end_action" default-value="revert_price">
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
                    <Select name="status" default-value="scheduled">
                        <SelectTrigger id="status" class="w-full">
                            <SelectValue placeholder="Pilih status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="draft">Draft</SelectItem>
                            <SelectItem value="scheduled">Scheduled</SelectItem>
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
        </div>
    </Modal>
</template>
