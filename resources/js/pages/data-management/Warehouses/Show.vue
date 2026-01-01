<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import IconActionButton from '@/components/ui/IconActionButton.vue';
import { Edit2, Trash2, ArrowLeft } from 'lucide-vue-next';
import AlertDialog from '@/components/ui/alert-dialog/AlertDialog.vue';
import AlertDialogAction from '@/components/ui/alert-dialog/AlertDialogAction.vue';
import AlertDialogCancel from '@/components/ui/alert-dialog/AlertDialogCancel.vue';
import AlertDialogContent from '@/components/ui/alert-dialog/AlertDialogContent.vue';
import AlertDialogDescription from '@/components/ui/alert-dialog/AlertDialogDescription.vue';
import AlertDialogHeader from '@/components/ui/alert-dialog/AlertDialogHeader.vue';
import AlertDialogTitle from '@/components/ui/alert-dialog/AlertDialogTitle.vue';
import ToastNotifications from '@/components/ToastNotifications.vue';
import { useToast } from '@/composables/useToast';
import { useLocalization } from '@/composables/useLocalization';

// Local types
interface BreadcrumbItem { title: string; href: string }
interface WarehouseProp { id: number; source?: string | null; quantity?: number | null; packing?: string | null; notes?: string | null }

const props = defineProps<{ warehouse: WarehouseProp }>();

const { t } = useLocalization();
const { success } = useToast();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  { title: t('data_management.data_management'), href: '/data-management/production' },
  { title: t('data_management.warehouse'), href: '/data-management/warehouses' },
  { title: props.warehouse.source ?? String(props.warehouse.id), href: `/data-management/warehouses/${props.warehouse.id}` },
]);

const showDeleteDialog = ref(false);
const deleting = ref(false);

const goBack = () => router.get('/data-management/warehouses');
const goEdit = () => router.get(`/data-management/warehouses/${props.warehouse.id}/edit`);

const openDeleteDialog = () => {
  showDeleteDialog.value = true;
};

const confirmDelete = () => {
  deleting.value = true;
  router.visit(`/data-management/warehouses/${props.warehouse.id}`, {
    method: 'delete',
    onSuccess: () => {
      success('Warehouse deleted', `${props.warehouse.source ?? props.warehouse.id} has been deleted`);
    },
    onFinish: () => (deleting.value = false),
  });
};
</script>

<template>
  <Head :title="`Warehouse — ${props.warehouse.source ?? props.warehouse.id}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <div class="rounded-xl border border-sidebar-border/70 p-6">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold">{{ props.warehouse.source ?? 'Warehouse Record' }}</h2>
            <p class="mt-1 text-sm text-muted-foreground">Details for this warehouse receipt.</p>
          </div>

          <div class="flex items-center gap-2">
            <IconActionButton :icon="ArrowLeft" label="Back" :onClick="goBack" />
            <IconActionButton :icon="Edit2" label="Edit" color="amber" :onClick="goEdit" />
            <IconActionButton :icon="Trash2" label="Delete" color="red" :onClick="openDeleteDialog" />
          </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-4">
          <div class="col-span-2 rounded-lg border p-4">
            <h3 class="font-medium">Source</h3>
            <div class="text-sm">{{ props.warehouse.source ?? '-' }}</div>
          </div>

          <div class="rounded-lg border p-4">
            <h3 class="font-medium">Quantity</h3>
            <div class="text-sm">{{ props.warehouse.quantity ?? '-' }}</div>
          </div>

          <div class="rounded-lg border p-4">
            <h3 class="font-medium">Packing</h3>
            <div class="text-sm">{{ props.warehouse.packing ?? '-' }}</div>
          </div>

          <div class="col-span-2 rounded-lg border p-4">
            <h3 class="font-medium">Notes</h3>
            <div class="text-sm">{{ props.warehouse.notes ?? '-' }}</div>
          </div>
        </div>
      </div>
      <div class="mt-6">
        <AlertDialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>Confirm Delete Warehouse</AlertDialogTitle>
              <AlertDialogDescription>
                Are you sure you want to delete "{{ props.warehouse.source ?? props.warehouse.id }}"? This action cannot be undone.
              </AlertDialogDescription>
            </AlertDialogHeader>
            <div class="flex justify-end gap-2">
              <AlertDialogCancel>Cancel</AlertDialogCancel>
              <AlertDialogAction @click="confirmDelete" :disabled="deleting" class="bg-red-600 hover:bg-red-700">Delete</AlertDialogAction>
            </div>
          </AlertDialogContent>
        </AlertDialog>

        <ToastNotifications />
      </div>
    </div>
  </AppLayout>
</template>
