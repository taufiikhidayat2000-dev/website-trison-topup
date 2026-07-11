<script setup lang="ts">
import { ReviewDataItem } from '@/types/cms/review';
import dayjs from 'dayjs';
import { Star } from 'lucide-vue-next';

defineProps<{
    average: number;
    total: number;
    reviews: ReviewDataItem[];
}>();
</script>

<template>
    <section v-if="reviews.length > 0" class="mb-8">
        <div class="mb-4 text-center">
            <h2 class="text-xl font-bold text-foreground">
                Ulasan dan Rating
            </h2>
            <div class="mt-2 flex items-center justify-center gap-2">
                <span class="text-3xl font-bold text-foreground">{{
                    average.toFixed(2)
                }}</span>
                <div class="flex items-center gap-0.5">
                    <Star
                        v-for="i in 5"
                        :key="i"
                        class="h-5 w-5"
                        :class="
                            i <= Math.round(average)
                                ? 'fill-yellow-400 text-yellow-400'
                                : 'text-muted-foreground'
                        "
                    />
                </div>
            </div>
            <p class="mt-1 text-sm text-muted-foreground">
                Berdasarkan total {{ total.toLocaleString('id-ID') }} rating
            </p>
        </div>

        <div class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-2">
            <div
                v-for="review in reviews"
                :key="review.id"
                class="w-72 shrink-0 snap-start rounded-lg border border-border/50 bg-card p-4 shadow-sm"
            >
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
                <p class="mt-3 line-clamp-3 text-sm text-foreground">
                    "{{ review.review }}"
                </p>
                <div class="mt-4 flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary"
                    >
                        {{ review.customer_name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-foreground">
                            {{ review.customer_name }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ review.game_name }} &middot;
                            {{ dayjs(review.created_at).format('DD MMM YYYY') }}
                        </p>
                    </div>
                </div>
                <div
                    v-if="review.admin_reply"
                    class="mt-3 rounded-md border border-primary/20 bg-primary/5 p-2 text-xs text-muted-foreground"
                >
                    <span class="font-semibold text-primary">Admin:</span>
                    {{ review.admin_reply }}
                </div>
            </div>
        </div>
    </section>
</template>
