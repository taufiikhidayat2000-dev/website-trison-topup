<script setup lang="ts">
import MainFooter from '@/components/MainFooter.vue';
import MainHeader from '@/components/MainHeader.vue';
import PaymentInstructions from '@/components/transaction/PaymentInstructions.vue';
import { DepositDataItem } from '@/types/cms/deposit';
import { PaymentDataItem } from '@/types/cms/main';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    deposit: DepositDataItem;
}>();

// PaymentInstructions.vue already knows how to render LinkQu VA/QRIS - reuse
// it as-is by mapping the deposit into the same shape it expects, instead of
// duplicating that markup for deposits.
const payment = computed(
    () =>
        ({
            driver: props.deposit.driver,
            payable_type: 'App\\Models\\Wallet\\Deposit',
            payable_id: props.deposit.id,
            order_id: props.deposit.reference,
            transaction_id: props.deposit.linkqu_reference ?? '',
            payment_type: props.deposit.payment_type ?? 'bank_transfer',
            account_number: props.deposit.account_number ?? '',
            account_code: props.deposit.account_code ?? '',
            channel: props.deposit.channel,
            expired_at: props.deposit.expired_at ?? null,
            paid_at: props.deposit.paid_at ?? null,
            amount: props.deposit.total_pay,
            created_at: props.deposit.created_at,
            updated_at: props.deposit.updated_at,
        }) as unknown as PaymentDataItem,
);
</script>

<template>
    <Head :title="'Top Up Saldo ' + deposit.reference" />

    <div class="flex min-h-screen flex-col bg-background">
        <MainHeader :show-back-button="true" />

        <main class="flex-1">
            <div class="mx-auto max-w-2xl px-4 py-8">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-foreground">
                        Top Up Saldo
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Referensi: {{ deposit.reference }}
                    </p>
                </div>

                <PaymentInstructions
                    :payment="payment"
                    :order-reference="deposit.reference"
                    :total-amount="deposit.total_pay"
                />

                <div v-if="deposit.status === 'paid'" class="mt-6 text-center">
                    <Link
                        href="/profile"
                        class="text-sm font-medium text-primary hover:underline"
                    >
                        Lihat Saldo Saya
                    </Link>
                </div>
            </div>
        </main>

        <MainFooter />
    </div>
</template>
