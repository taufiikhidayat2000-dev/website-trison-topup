<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { PPOBBrandManualField } from '@/types/cms/ppob';
import { CircleAlert } from 'lucide-vue-next';

const props = defineProps<{
    fields: PPOBBrandManualField[];
    modelValue: Record<string, string>;
    formErrors: any;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: Record<string, string>];
}>();

const updateField = (key: string, value: string) => {
    emit('update:modelValue', { ...props.modelValue, [key]: value });
};
</script>

<template>
    <section class="rounded-lg border border-border/50 bg-card p-6 shadow-sm">
        <h2
            class="mb-4 flex items-center gap-2 text-lg font-bold text-foreground"
        >
            <span
                class="flex h-7 w-7 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground"
                >1</span
            >
            Masukkan Data Akun
        </h2>

        <p
            class="mb-6 flex items-center gap-2 rounded-md bg-secondary p-3 text-sm text-muted-foreground"
        >
            <CircleAlert class="h-4 w-4 flex-shrink-0 text-destructive" />
            Pastikan data login yang kamu masukkan benar. Data ini digunakan
            admin untuk memproses top up ke akunmu.
        </p>

        <div class="grid gap-4 sm:grid-cols-2">
            <div v-for="field in fields" :key="field.key">
                <Label :for="`manual-field-${field.key}`">
                    {{ field.label }}
                    <span v-if="field.required" class="text-destructive"
                        >*</span
                    >
                </Label>

                <Select
                    v-if="field.type === 'select'"
                    :model-value="modelValue[field.key] ?? ''"
                    @update:model-value="updateField(field.key, String($event))"
                >
                    <SelectTrigger
                        :id="`manual-field-${field.key}`"
                        class="mt-1 w-full"
                    >
                        <SelectValue :placeholder="`Pilih ${field.label}`" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in field.options ?? []"
                            :key="option"
                            :value="option"
                        >
                            {{ option }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Input
                    v-else
                    :id="`manual-field-${field.key}`"
                    :type="field.type"
                    :model-value="modelValue[field.key] ?? ''"
                    :placeholder="`Masukkan ${field.label}`"
                    class="mt-1"
                    @update:model-value="updateField(field.key, String($event))"
                />

                <InputError
                    :message="formErrors[`manual_fields.${field.key}`]"
                    class="mt-1"
                />
            </div>
        </div>
    </section>
</template>
