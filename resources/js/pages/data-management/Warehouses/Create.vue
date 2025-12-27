<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import ToastNotifications from '@/components/ToastNotifications.vue';
import { useToast } from '@/composables/useToast';
import AlertDialog from '@/components/ui/alert-dialog/AlertDialog.vue';
import AlertDialogAction from '@/components/ui/alert-dialog/AlertDialogAction.vue';
import AlertDialogCancel from '@/components/ui/alert-dialog/AlertDialogCancel.vue';
import AlertDialogContent from '@/components/ui/alert-dialog/AlertDialogContent.vue';
import AlertDialogDescription from '@/components/ui/alert-dialog/AlertDialogDescription.vue';
import AlertDialogHeader from '@/components/ui/alert-dialog/AlertDialogHeader.vue';
import AlertDialogTitle from '@/components/ui/alert-dialog/AlertDialogTitle.vue';

// Local breadcrumb shape to avoid cross-file typings
interface BreadcrumbItem { title: string; href: string }

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Data Management', href: '/data-management/production' },
  { title: 'Warehouse', href: '/data-management/warehouses' },
  { title: 'Create', href: '/data-management/warehouses/create' },
];

const { success } = useToast();

const form = useForm({
  source: '',
  quantity: 0,
  packing: '',
  notes: '',
  is_active: true,
});

const errors = ref<Record<string,string>>({});
const submitting = ref(false);
const showConfirmDialog = ref(false);

const validate = (): boolean => {
  const newErrors: Record<string,string> = {};
  if (form.quantity === null || form.quantity === undefined) newErrors.quantity = 'Quantity is required';
  else if (form.quantity < 0) newErrors.quantity = 'Quantity must be 0 or greater';
  errors.value = newErrors;
  return Object.keys(newErrors).length === 0;
};

const handleSubmit = () => {
  if (validate()) showConfirmDialog.value = true;
};

const confirmSubmit = () => {
  submitting.value = true;
  form.post('/data-management/warehouses', {
    onSuccess: () => {
      success('Warehouse created', 'New warehouse record has been saved');
      router.get('/data-management/warehouses');
    },
    onFinish: () => (submitting.value = false),
  });
};
</script>

<template>
  <Head title="Create Warehouse Record" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <div class="rounded-xl border border-sidebar-border/70 p-6">
        <h2 class="text-lg font-semibold">Create Warehouse Record</h2>
        <p class="mt-2 text-sm text-muted-foreground">Add a new warehouse receipt from production.</p>

        <div class="mt-4">
          <div class="grid grid-cols-2 gap-4">
            <label class="block col-span-2">
              <div class="text-sm font-medium">Source (Dari P4)</div>
              <input v-model="form.source" type="text" placeholder="Optional source" class="input w-full" />
            </label>

            <label class="block">
              <div class="text-sm font-medium">Jumlah Lembar Diterima (Quantity) <span class="text-red-500">*</span></div>
              <input v-model.number="form.quantity" type="number" min="0" class="input w-full" />
              <p v-if="errors.quantity" class="mt-1 text-xs text-red-500">{{ errors.quantity }}</p>
            </label>

            <label class="block">
              <div class="text-sm font-medium">Sudah Packing / Crate (Packing)</div>
              <input v-model="form.packing" type="text" placeholder="Optional" class="input w-full" />
            </label>

            <label class="block col-span-2">
              <div class="text-sm font-medium">Keterangan (Notes)</div>
              <textarea v-model="form.notes" placeholder="Optional notes" class="input h-28 w-full"></textarea>
            </label>
          </div>

          <div class="mt-4 flex justify-end">
            <button class="btn-secondary" @click="router.get('/data-management/warehouses')">Cancel</button>
            <button class="btn ml-2" :disabled="submitting" @click="handleSubmit">Create</button>
          </div>
        </div>
      </div>
    </div>

    <AlertDialog :open="showConfirmDialog" @update:open="showConfirmDialog = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Confirm Create Warehouse Record</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to create this warehouse record?
          </AlertDialogDescription>
        </AlertDialogHeader>

        <div class="rounded-lg bg-muted/50 p-3">
          <div class="space-y-1 text-sm">
            <div><span class="font-medium">Source:</span> {{ form.source || '-' }}</div>
            <div><span class="font-medium">Quantity:</span> {{ form.quantity ?? '-' }}</div>
            <div><span class="font-medium">Packing:</span> {{ form.packing || '-' }}</div>
          </div>
        </div>

        <div class="flex justify-end gap-2">
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction @click="confirmSubmit" :disabled="submitting">Create</AlertDialogAction>
        </div>
      </AlertDialogContent>
    </AlertDialog>

    <ToastNotifications />
  </AppLayout>
</template>
