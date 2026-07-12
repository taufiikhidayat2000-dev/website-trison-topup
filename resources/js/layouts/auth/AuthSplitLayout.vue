<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const name = page.props.setting?.title || page.props.name;
const logo = page.props.setting?.logo;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex h-dvh w-full flex-col overflow-hidden bg-gradient-to-br from-[#04081c] via-[#081335] to-[#020512]"
    >
        <div
            class="absolute inset-0 bg-[url('/images/auth-bg.png')] bg-cover bg-center bg-no-repeat"
        />
        <div class="absolute inset-0 bg-black/30" />

        <div
            class="relative z-10 flex h-full flex-col items-center justify-center gap-8 overflow-y-auto px-4 py-10"
        >
            <Link :href="home()" class="flex items-center gap-2 text-white">
                <img
                    v-if="logo"
                    :src="logo"
                    alt="Logo"
                    class="h-8 w-8 rounded-md object-cover"
                />
                <AppLogoIcon v-else class="size-8 fill-current text-white" />
                <span class="text-lg font-semibold">{{ name }}</span>
            </Link>

            <div class="flex w-full flex-col items-center gap-6 sm:w-[350px]">
                <div
                    class="flex flex-col gap-2 text-center"
                    v-if="title || description"
                >
                    <h1
                        class="text-xl font-medium tracking-tight text-white"
                        v-if="title"
                    >
                        {{ title }}
                    </h1>
                    <p class="text-sm text-neutral-300" v-if="description">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
