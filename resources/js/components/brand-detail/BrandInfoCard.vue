<script setup lang="ts">
import { BookOpenText, Info } from 'lucide-vue-next';

defineProps<{
    description?: string | null;
    caraTopup?: string | null;
}>();
</script>

<template>
    <div
        v-if="description || caraTopup"
        class="space-y-5 rounded-lg border border-border/50 bg-card p-6 shadow-sm"
    >
        <div v-if="description">
            <div class="mb-2 flex items-center gap-2">
                <Info class="h-4 w-4 text-primary" />
                <h3 class="text-sm font-semibold text-foreground">
                    Deskripsi Produk
                </h3>
            </div>
            <div
                v-safe-html="description"
                class="prose prose-sm max-w-none text-sm text-muted-foreground"
            ></div>
        </div>

        <div
            v-if="description && caraTopup"
            class="border-t border-border/30"
        ></div>

        <div v-if="caraTopup">
            <div class="mb-2 flex items-center gap-2">
                <BookOpenText class="h-4 w-4 text-primary" />
                <h3 class="text-sm font-semibold text-foreground">
                    Cara Topup
                </h3>
            </div>
            <div
                v-safe-html="caraTopup"
                class="prose prose-sm max-w-none text-sm text-muted-foreground"
            ></div>
        </div>
    </div>
</template>
