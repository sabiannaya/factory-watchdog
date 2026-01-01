<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useLocalization } from '@/composables/useLocalization';
// Breadcrumbs are simple arrays; avoid importing project-wide types which may not resolve here
interface BreadcrumbItem { title: string; href: string }
interface WarehouseProp { id: number; source?: string | null; quantity?: number | null; packing?: string | null; notes?: string | null; is_active?: boolean | null }
const props = defineProps<{ warehouse: WarehouseProp }>();

const { t } = useLocalization();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  { title: t('data_management.data_management'), href: '/data-management/production' },
  { title: t('data_management.warehouse'), href: '/data-management/warehouses' },
  { title: t('data_management.edit'), href: '' },
]);

const form = useForm({
  source: props.warehouse.source ?? '',
  quantity: props.warehouse.quantity ?? 0,
  packing: props.warehouse.packing ?? '',
  notes: props.warehouse.notes ?? '',
  is_active: props.warehouse.is_active ?? true,
});

const errors = ref<Record<string,string>>({});

const submit = () => {
  errors.value = {};
  if (form.quantity === null || form.quantity === undefined) {
    errors.value.quantity = 'Quantity is required';
    return;
  }
  if (form.quantity < 0) {
    errors.value.quantity = 'Quantity must be 0 or greater';
    return;
  }

  form.put(`/data-management/warehouses/${props.warehouse.id}`, {
    onSuccess: () => router.get('/data-management/warehouses'),
  });
};
</script>

<template>
  <Head :title="`Edit Warehouse — ${props.warehouse.source ?? props.warehouse.id}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <div class="rounded-xl border border-sidebar-border/70 p-6">
        <h2 class="text-lg font-semibold">Edit Warehouse Record</h2>
        <p class="mt-2 text-sm text-muted-foreground">Update details for this warehouse receipt.</p>

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
            <button class="btn ml-2" :disabled="form.processing">{{ form.processing ? 'Saving...' : 'Save' }}</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
