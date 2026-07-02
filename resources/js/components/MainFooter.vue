<script setup lang="ts">
import { home, privacyPolicy, terms } from '@/routes';
import { check } from '@/routes/transaction';
import { Link, usePage } from '@inertiajs/vue3';
import { Facebook, Instagram, Mail, Phone, Twitter } from 'lucide-vue-next';
const page = usePage();
</script>

<template>
    <footer class="border-t border-cyan-400/20 bg-[#0B1020] text-white shadow-[0_-8px_30px_rgba(0,0,0,0.35)]">
        <div class="mx-auto max-w-7xl px-4 py-12">
            <div class="grid gap-8 md:grid-cols-4">
                <!-- Company Info -->
                <div class="md:col-span-2">
                    <h3 class="mb-4 text-lg font-bold text-white">
                        {{ page.props.name }}
                    </h3>
                    <p class="mb-4 text-sm leading-relaxed text-slate-300">
                        {{ page.props.setting.footer_description }}
                    </p>
                    <div class="flex gap-4">
                        <a
                            :href="page.props.setting.footer_facebook_url || '#'"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-slate-300 transition-colors hover:text-cyan-300"
                        >
                            <Facebook class="h-5 w-5" />
                        </a>
                        <a
                            :href="page.props.setting.footer_instagram_url || '#'"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-slate-300 transition-colors hover:text-cyan-300"
                        >
                            <Instagram class="h-5 w-5" />
                        </a>
                        <a
                            :href="page.props.setting.footer_twitter_url || '#'"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-slate-300 transition-colors hover:text-cyan-300"
                        >
                            <Twitter class="h-5 w-5" />
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="mb-4 font-semibold text-white">
                        Quick Links
                    </h4>
                    <ul class="space-y-2 text-sm">
                        <li>
                            <Link
                                :href="home().url"
                                class="text-slate-300 transition-colors hover:text-cyan-300"
                            >
                                Home
                            </Link>
                        </li>
                        <li>
                            <Link
                                :href="check().url"
                                class="text-slate-300 transition-colors hover:text-cyan-300"
                            >
                                Cek Transaksi
                            </Link>
                        </li>
                        <li>
                            <Link
                                :href="privacyPolicy().url"
                                class="text-slate-300 transition-colors hover:text-cyan-300"
                            >
                                Kebijakan Privasi
                            </Link>
                        </li>
                        <li>
                            <Link
                                :href="terms().url"
                                class="text-slate-300 transition-colors hover:text-cyan-300"
                            >
                                Syarat & Ketentuan
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="mb-4 font-semibold text-white">Kontak</h4>
                    <ul class="space-y-3 text-sm">
                        <li class="flex items-center gap-2 text-slate-300">
                            <Mail class="h-4 w-4 text-cyan-300" />
                            <span>{{ page.props.setting.email }}</span>
                        </li>
                        <li class="flex items-center gap-2 text-slate-300">
                            <Phone class="h-4 w-4 text-cyan-300" />
                            <span>{{ page.props.setting.phone }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Copyright -->
            <div
                class="mt-8 border-t border-cyan-400/10 pt-8 text-center text-sm text-slate-400"
            >
                <p>
                    {{
                        page.props.setting.footer_copyright ||
                        `© ${new Date().getFullYear()} Trison Topup. All rights reserved.`
                    }}
                </p>
            </div>
        </div>
    </footer>
</template>
