<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/Main/ReviewController';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useSwal } from '@/composables/useSwal';
import { ReviewDataItem } from '@/types/cms/review';
import { useForm } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { MessageSquareQuote, Star } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    orderReference: string;
    isEligibleForReview: boolean;
    review?: ReviewDataItem | null;
}>();

const { toast } = useSwal();

const hoveredStar = ref(0);

const form = useForm({
    rating: 5,
    review: '',
});

const submit = () => {
    form.post(store({ order: props.orderReference }).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.fire({
                icon: 'success',
                title: 'Terima kasih atas ulasan Anda!',
            });
        },
    });
};
</script>

<template>
    <Card v-if="review" class="mt-6 p-6">
        <h2 class="mb-4 flex items-center text-lg font-bold">
            <MessageSquareQuote class="mr-2 h-5 w-5" />
            Ulasan Anda
        </h2>

        <div class="flex items-center gap-1">
            <Star
                v-for="i in 5"
                :key="i"
                class="h-5 w-5"
                :class="
                    i <= review.rating
                        ? 'fill-yellow-400 text-yellow-400'
                        : 'text-muted-foreground'
                "
            />
        </div>
        <p class="mt-3 text-sm text-foreground">{{ review.review }}</p>
        <p class="mt-2 text-xs text-muted-foreground">
            {{ dayjs(review.created_at).format('DD MMM YYYY, HH:mm') }}
        </p>

        <div
            v-if="review.admin_reply"
            class="mt-4 rounded-lg border border-primary/30 bg-primary/5 p-4"
        >
            <p class="text-xs font-semibold text-primary">Balasan Admin</p>
            <p class="mt-1 text-sm text-foreground">
                {{ review.admin_reply }}
            </p>
        </div>
    </Card>

    <Card v-else-if="isEligibleForReview" class="mt-6 p-6">
        <h2 class="mb-4 flex items-center text-lg font-bold">
            <MessageSquareQuote class="mr-2 h-5 w-5" />
            Bagaimana pengalaman Anda?
        </h2>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="flex items-center gap-1">
                <button
                    v-for="i in 5"
                    :key="i"
                    type="button"
                    class="p-0.5"
                    @mouseenter="hoveredStar = i"
                    @mouseleave="hoveredStar = 0"
                    @click="form.rating = i"
                >
                    <Star
                        class="h-8 w-8 transition-colors"
                        :class="
                            i <= (hoveredStar || form.rating)
                                ? 'fill-yellow-400 text-yellow-400'
                                : 'text-muted-foreground'
                        "
                    />
                </button>
            </div>

            <div class="grid gap-2">
                <Label for="review">Tulis ulasan Anda</Label>
                <Textarea
                    id="review"
                    v-model="form.review"
                    placeholder="Ceritakan pengalaman Anda berbelanja di sini... (minimal 10 karakter)"
                    rows="4"
                    maxlength="500"
                />
                <p class="text-right text-xs text-muted-foreground">
                    {{ form.review.length }}/500
                </p>
                <p v-if="form.errors.review" class="text-sm text-destructive">
                    {{ form.errors.review }}
                </p>
                <p
                    v-if="form.errors.rating"
                    class="text-sm text-destructive"
                >
                    {{ form.errors.rating }}
                </p>
            </div>

            <Button
                type="submit"
                class="w-full"
                :disabled="form.processing || form.review.length < 10"
            >
                Kirim Ulasan
            </Button>
        </form>
    </Card>
</template>
