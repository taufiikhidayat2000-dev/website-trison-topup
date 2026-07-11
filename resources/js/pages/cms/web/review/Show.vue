<script setup lang="ts">
import { reply } from '@/actions/App/Http/Controllers/Cms/Web/ReviewController';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useSwal } from '@/composables/useSwal';
import { ReviewDataItem } from '@/types/cms/review';
import { useForm } from '@inertiajs/vue3';
import { Modal } from '@inertiaui/modal-vue';
import dayjs from 'dayjs';
import { Star } from 'lucide-vue-next';

const props = defineProps<{
    review: ReviewDataItem;
}>();

const { toast } = useSwal();

const form = useForm({
    admin_reply: props.review.admin_reply ?? '',
});

const submit = (close: () => void) => {
    form.post(reply({ review: props.review.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.fire({
                icon: 'success',
                title: 'Balasan berhasil dikirim.',
            });
            close();
        },
    });
};
</script>

<template>
    <Modal v-slot="{ close }">
        <div class="p-6">
            <h2 class="text-lg font-medium">Detail Review</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ review.order?.reference }} &middot;
                {{ dayjs(review.created_at).format('DD MMMM YYYY, HH:mm') }}
            </p>

            <div class="mt-6 space-y-4 rounded-lg border bg-muted/30 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold">{{ review.customer_name }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ review.game_name }} &middot;
                            {{ review.product_name }}
                        </p>
                    </div>
                    <div class="flex items-center gap-1">
                        <Star
                            v-for="i in 5"
                            :key="i"
                            class="h-4 w-4"
                            :class="
                                i <= review.rating
                                    ? 'fill-yellow-400 text-yellow-400'
                                    : 'text-muted-foreground'
                            "
                        />
                    </div>
                </div>
                <p class="text-sm text-foreground">{{ review.review }}</p>
            </div>

            <div class="mt-6 grid gap-2">
                <Label for="admin_reply">Balasan Admin</Label>
                <Textarea
                    id="admin_reply"
                    v-model="form.admin_reply"
                    placeholder="Terima kasih atas ulasannya 🙏"
                    rows="4"
                    maxlength="1000"
                />
                <p v-if="form.errors.admin_reply" class="text-sm text-destructive">
                    {{ form.errors.admin_reply }}
                </p>
            </div>

            <div class="mt-6 flex justify-end gap-4">
                <Button
                    :disabled="form.processing"
                    @click="submit(close)"
                >
                    Kirim Balasan
                </Button>
            </div>
        </div>
    </Modal>
</template>
