<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useSwal } from '@/composables/useSwal';
import { PPOBBrandManualField } from '@/types/cms/ppob';
import { Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const { toast } = useSwal();

const props = withDefaults(
    defineProps<{
        modelValue: PPOBBrandManualField[];
        namePrefix?: string;
    }>(),
    {
        namePrefix: 'settings[manual_fields]',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: PPOBBrandManualField[]];
}>();

const fieldTypeLabels: Record<PPOBBrandManualField['type'], string> = {
    text: 'Text',
    email: 'Email',
    password: 'Password',
    select: 'Dropdown',
};

const newField = ref<{
    label: string;
    type: PPOBBrandManualField['type'];
    options: string;
    required: boolean;
}>({
    label: '',
    type: 'text',
    options: '',
    required: true,
});

const slugifyKey = (label: string, existing: PPOBBrandManualField[]) => {
    const base =
        label
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '') || 'field';

    let key = base;
    let suffix = 1;
    while (existing.some((field) => field.key === key)) {
        key = `${base}_${suffix}`;
        suffix += 1;
    }

    return key;
};

const addField = () => {
    if (!newField.value.label.trim()) {
        toast.fire({
            icon: 'error',
            title: 'Mohon isi Label Field terlebih dahulu',
        });
        return;
    }

    const options =
        newField.value.type === 'select'
            ? newField.value.options
                  .split(',')
                  .map((option) => option.trim())
                  .filter(Boolean)
            : undefined;

    if (
        newField.value.type === 'select' &&
        (!options || options.length === 0)
    ) {
        toast.fire({
            icon: 'error',
            title: 'Mohon isi minimal satu opsi dropdown',
        });
        return;
    }

    const field: PPOBBrandManualField = {
        key: slugifyKey(newField.value.label, props.modelValue),
        label: newField.value.label.trim(),
        type: newField.value.type,
        options,
        required: newField.value.required,
    };

    emit('update:modelValue', [...props.modelValue, field]);

    newField.value = { label: '', type: 'text', options: '', required: true };
};

const deleteField = (index: number) => {
    const next = [...props.modelValue];
    next.splice(index, 1);
    emit('update:modelValue', next);
};
</script>

<template>
    <div class="space-y-4">
        <!-- Hidden inputs so the field list submits with the surrounding <Form> -->
        <template v-for="(field, index) in modelValue" :key="field.key">
            <input
                type="hidden"
                :name="`${namePrefix}[${index}][key]`"
                :value="field.key"
            />
            <input
                type="hidden"
                :name="`${namePrefix}[${index}][label]`"
                :value="field.label"
            />
            <input
                type="hidden"
                :name="`${namePrefix}[${index}][type]`"
                :value="field.type"
            />
            <input
                type="hidden"
                :name="`${namePrefix}[${index}][required]`"
                :value="field.required ? 1 : 0"
            />
            <input
                v-for="(option, optionIndex) in field.options ?? []"
                :key="optionIndex"
                type="hidden"
                :name="`${namePrefix}[${index}][options][${optionIndex}]`"
                :value="option"
            />
        </template>

        <!-- Add field mini-form -->
        <div
            class="grid gap-3 rounded-md border bg-muted/30 p-4 sm:grid-cols-2"
        >
            <div class="grid gap-1.5">
                <span class="text-xs font-medium text-muted-foreground"
                    >Label Field</span
                >
                <Input
                    v-model="newField.label"
                    placeholder="Contoh: Email, Password, Nickname, Login Via"
                />
            </div>

            <div class="grid gap-1.5">
                <span class="text-xs font-medium text-muted-foreground"
                    >Tipe Input</span
                >
                <Select v-model="newField.type">
                    <SelectTrigger class="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="text">Text</SelectItem>
                        <SelectItem value="email">Email</SelectItem>
                        <SelectItem value="password">Password</SelectItem>
                        <SelectItem value="select">Dropdown</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div
                v-if="newField.type === 'select'"
                class="grid gap-1.5 sm:col-span-2"
            >
                <span class="text-xs font-medium text-muted-foreground">
                    Opsi Dropdown (pisahkan dengan koma)
                </span>
                <Input
                    v-model="newField.options"
                    placeholder="Contoh: Google, Facebook, Guest"
                />
            </div>

            <div class="flex items-center gap-2">
                <Checkbox
                    :model-value="newField.required"
                    @update:model-value="
                        (value) => (newField.required = value === true)
                    "
                />
                <span class="text-sm text-foreground">Wajib diisi</span>
            </div>

            <div class="flex items-end justify-end">
                <Button type="button" size="sm" @click="addField">
                    <Plus class="mr-2 h-4 w-4" />
                    Tambah Field
                </Button>
            </div>
        </div>

        <!-- Field list table -->
        <div class="rounded-md border">
            <table class="w-full">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th class="px-4 py-3 text-left text-sm font-medium">
                            Label
                        </th>
                        <th class="px-4 py-3 text-left text-sm font-medium">
                            Key
                        </th>
                        <th class="px-4 py-3 text-left text-sm font-medium">
                            Tipe
                        </th>
                        <th class="px-4 py-3 text-left text-sm font-medium">
                            Wajib
                        </th>
                        <th class="px-4 py-3 text-right text-sm font-medium">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="modelValue.length === 0" class="border-b">
                        <td
                            colspan="5"
                            class="px-4 py-8 text-center text-sm text-muted-foreground"
                        >
                            Belum ada field manual. Tambahkan field seperti
                            email, password, nickname, server, atau login via.
                        </td>
                    </tr>
                    <tr
                        v-for="(field, index) in modelValue"
                        :key="field.key"
                        class="border-b transition-colors hover:bg-muted/50"
                    >
                        <td class="px-4 py-3 text-sm">
                            {{ field.label }}
                        </td>
                        <td
                            class="px-4 py-3 font-mono text-sm text-muted-foreground"
                        >
                            {{ field.key }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            {{ fieldTypeLabels[field.type] }}
                            <span
                                v-if="field.type === 'select'"
                                class="text-xs text-muted-foreground"
                            >
                                ({{ (field.options ?? []).join(', ') }})
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            {{ field.required ? 'Ya' : 'Tidak' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="deleteField(index)"
                            >
                                <Trash2 class="h-4 w-4 text-destructive" />
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
