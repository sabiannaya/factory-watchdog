<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { type BreadcrumbItem, type ProductFormData, type ProductFormErrors } from '@/types';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Data Management', href: '/data-management/production' },
    { title: 'Products', href: '/data-management/products' },
    { title: 'Create', href: '/data-management/products/create' },
];

const form = ref<ProductFormData>({ name: '', thickness: '', ply: '', glue_type: '', qty: 0, notes: '' });
const errors = ref<ProductFormErrors>({});
const submitting = ref(false);
const showConfirmDialog = ref(false);

const validate = (): boolean => {
    errors.value = {};
    const newErrors: ProductFormErrors = {};

    if (!form.value.name?.trim()) newErrors.name = 'Name is required';
    if (!form.value.thickness?.trim()) newErrors.thickness = 'Thickness is required';
    if (!form.value.ply?.trim()) newErrors.ply = 'Ply is required';
    if (!form.value.glue_type?.trim()) newErrors.glue_type = 'Glue Type is required';
    if (!form.value.qty || form.value.qty < 0) newErrors.qty = 'Qty must be a valid number';

    errors.value = newErrors;
    return Object.keys(newErrors).length === 0;
};

const hasErrors = computed(() => Object.keys(errors.value).length > 0);

const handleSubmit = () => {
    if (validate()) {
        showConfirmDialog.value = true;
    }
};

const confirmSubmit = () => {
    submitting.value = true;
    router.post('/data-management/products', form.value, {
        onFinish: () => (submitting.value = false),
    });
};
</script>

<template>
  <Head title="Create Product" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <div class="rounded-xl border border-sidebar-border/70 p-6">
        <h2 class="text-lg font-semibold">Create Product</h2>
        <p class="mt-2 text-sm text-muted-foreground">Add a new product setting used in production.</p>

        <div class="mt-4">
          <div class="grid grid-cols-2 gap-4">
            <label class="block">
              <div class="text-sm font-medium">Name <span class="text-red-500">*</span></div>
              <input v-model="form.name" type="text" placeholder="Enter product name" class="input" :class="{ 'border-red-500': errors.name }" />
              <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name }}</p>
            </label>

            <label class="block">
              <div class="text-sm font-medium">Thickness <span class="text-red-500">*</span></div>
              <input v-model="form.thickness" type="text" placeholder="e.g., 3mm" class="input" :class="{ 'border-red-500': errors.thickness }" />
              <p v-if="errors.thickness" class="mt-1 text-xs text-red-500">{{ errors.thickness }}</p>
            </label>

            <label class="block">
              <div class="text-sm font-medium">Ply <span class="text-red-500">*</span></div>
              <input v-model="form.ply" type="text" placeholder="e.g., 2" class="input" :class="{ 'border-red-500': errors.ply }" />
              <p v-if="errors.ply" class="mt-1 text-xs text-red-500">{{ errors.ply }}</p>
            </label>

            <label class="block">
              <div class="text-sm font-medium">Glue Type <span class="text-red-500">*</span></div>
              <input v-model="form.glue_type" type="text" placeholder="e.g., PVA, PU" class="input" :class="{ 'border-red-500': errors.glue_type }" />
              <p v-if="errors.glue_type" class="mt-1 text-xs text-red-500">{{ errors.glue_type }}</p>
            </label>

            <label class="block">
              <div class="text-sm font-medium">Qty <span class="text-red-500">*</span></div>
              <input v-model.number="form.qty" type="number" placeholder="0" class="input" :class="{ 'border-red-500': errors.qty }" />
              <p v-if="errors.qty" class="mt-1 text-xs text-red-500">{{ errors.qty }}</p>
            </label>

            <label class="block col-span-2">
              <div class="text-sm font-medium">Notes</div>
              <textarea v-model="form.notes" placeholder="Optional notes about this product" class="input"></textarea>
            </label>
          </div>

          <div class="mt-4 flex justify-end">
            <button class="btn hover:cursor-pointer" :disabled="submitting" @click="handleSubmit">Create</button>
          </div>
        </div>
      </div>
    </div>

    <AlertDialog :open="showConfirmDialog" @update:open="showConfirmDialog = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Confirm Create Product</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to create this product? This action can be undone by deleting it later.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <div class="rounded-lg bg-muted/50 p-3">
          <div class="space-y-1 text-sm">
            <div><span class="font-medium">Name:</span> {{ form.name }}</div>
            <div><span class="font-medium">Thickness:</span> {{ form.thickness }}</div>
            <div><span class="font-medium">Ply:</span> {{ form.ply }}</div>
            <div><span class="font-medium">Glue Type:</span> {{ form.glue_type }}</div>
            <div><span class="font-medium">Qty:</span> {{ form.qty }}</div>
          </div>
        </div>
        <div class="flex justify-end gap-2">
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction @click="confirmSubmit" :disabled="submitting">Create</AlertDialogAction>
        </div>
      </AlertDialogContent>
    </AlertDialog>
  </AppLayout>
</template>
