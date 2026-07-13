<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/Cms/PPOB/PPOBProductCategoryController';
import ImageUploadPreview from '@/components/ImageUploadPreview.vue';
import InputDescription from '@/components/InputDescription.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import QuilTextEditor from '@/components/ui/quil-editor/QuilTextEditor.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useSwal } from '@/composables/useSwal';
import { Form } from '@inertiajs/vue3';
import { Modal } from '@inertiaui/modal-vue';
import { Save } from 'lucide-vue-next';
import { ref } from 'vue';

const { toast } = useSwal();

// Description state
const description = ref<string>('');
</script>

<template>
    <Modal v-slot="{ close }">
        <div class="p-6">
            <h2 class="text-lg font-medium">Create Product Category</h2>

            <p class="mt-1 text-sm text-muted-foreground">
                Create a new product category to help customers filter products
                within a brand (e.g. Diamonds, Weekly Pass, Subscription).
            </p>

            <Form
                v-bind="store.form()"
                class="mt-6 space-y-6"
                @success="
                    () => {
                        toast.fire({
                            icon: 'success',
                            title: 'Product Category created successfully.',
                        });
                        close();
                    }
                "
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <InputDescription>
                        The name of the product category.
                    </InputDescription>
                    <Input
                        id="name"
                        name="name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autofocus
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <InputDescription>
                        A brief description of the product category.
                    </InputDescription>
                    <Input
                        id="description"
                        name="description"
                        type="hidden"
                        :value="description"
                    />
                    <QuilTextEditor
                        @update:content="
                            (value) => {
                                description = value;
                            }
                        "
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="image">Image</Label>
                    <InputDescription>
                        Upload a default image for this product category (Max
                        2MB). Every product in this category that doesn't have
                        its own photo will use this image automatically.
                        Recommended size: 500x500 px (square).
                    </InputDescription>
                    <ImageUploadPreview
                        input-id="image"
                        input-name="image"
                        label=""
                        description="Upload your product category image here. Recommended size: 500x500 px (square)."
                        accept="image/*"
                        :max-size="2"
                        preview-height="200px"
                        :errors="errors.image"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="status">Status</Label>
                    <InputDescription>
                        Select the status of the product category.
                    </InputDescription>
                    <Select name="status" default-value="1">
                        <SelectTrigger id="status" class="mt-1 w-full">
                            <SelectValue placeholder="Select a status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="1">Active</SelectItem>
                            <SelectItem value="0">Inactive</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.status" />
                </div>

                <div class="flex justify-end gap-4">
                    <Button :disabled="processing" type="submit">
                        <Save />
                        Save Changes
                    </Button>
                </div>
            </Form>
        </div>
    </Modal>
</template>
