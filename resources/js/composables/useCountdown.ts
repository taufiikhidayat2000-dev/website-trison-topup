import { computed, MaybeRef, onMounted, onUnmounted, ref, unref } from 'vue';

export function useCountdown(endDate: MaybeRef<string | Date>) {
    const remaining = ref(0);
    let timer: ReturnType<typeof setInterval> | null = null;

    const tick = () => {
        const end = new Date(unref(endDate)).getTime();
        remaining.value = Math.max(0, end - Date.now());
    };

    onMounted(() => {
        tick();
        timer = setInterval(tick, 1000);
    });

    onUnmounted(() => {
        if (timer) {
            clearInterval(timer);
        }
    });

    const totalSeconds = computed(() => Math.floor(remaining.value / 1000));
    const days = computed(() => Math.floor(totalSeconds.value / 86400));
    const hours = computed(() => Math.floor((totalSeconds.value % 86400) / 3600));
    const minutes = computed(() => Math.floor((totalSeconds.value % 3600) / 60));
    const seconds = computed(() => totalSeconds.value % 60);
    const isOver = computed(() => remaining.value <= 0);

    const pad = (n: number) => n.toString().padStart(2, '0');

    return {
        remaining,
        days,
        hours,
        minutes,
        seconds,
        isOver,
        pad,
    };
}
