<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type GlueSpreaderItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import IconActionButton from '@/components/ui/IconActionButton.vue';
import { Edit2, Trash2 } from 'lucide-vue-next';
import AlertDialog from '@/components/ui/alert-dialog/AlertDialog.vue';
import AlertDialogAction from '@/components/ui/alert-dialog/AlertDialogAction.vue';
import AlertDialogCancel from '@/components/ui/alert-dialog/AlertDialogCancel.vue';
import AlertDialogContent from '@/components/ui/alert-dialog/AlertDialogContent.vue';
import AlertDialogDescription from '@/components/ui/alert-dialog/AlertDialogDescription.vue';
import AlertDialogHeader from '@/components/ui/alert-dialog/AlertDialogHeader.vue';
import AlertDialogTitle from '@/components/ui/alert-dialog/AlertDialogTitle.vue';
import ToastNotifications from '@/components/ToastNotifications.vue';
import { useToast } from '@/composables/useToast';

const props = defineProps<{ glueSpreader: GlueSpreaderItem }>();

const { success } = useToast();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Data Management', href: '/data-management/production' },
  { title: 'Glue Spreaders', href: '/data-management/glue-spreaders' },
  {
    title: props.glueSpreader.name,
    href: `/data-management/glue-spreaders/${props.glueSpreader.id}`,
  },
];

const showDeleteDialog = ref(false);
const deleting = ref(false);

const goBack = () => router.get('/data-management/glue-spreaders');
const goEdit = () => router.get(`/data-management/glue-spreaders/${props.glueSpreader.id}/edit`);

const openDeleteDialog = () => {
  showDeleteDialog.value = true;
};

const confirmDelete = () => {
  deleting.value = true;
  router.visit(`/data-management/glue-spreaders/${props.glueSpreader.id}`, {
    method: 'delete',
    onSuccess: () => {
      success('Glue Spreader deleted', `${props.glueSpreader.name} has been deleted successfully`);
    },
    onFinish: () => (deleting.value = false),
  });
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head :title="`Glue Spreader — ${props.glueSpreader.name}`" />
    <div class="p-4">
      <div class="rounded-xl border border-sidebar-border/70 p-6">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold">{{ props.glueSpreader.name }}</h2>
            <p class="mt-1 text-sm text-muted-foreground">Glue Spreader details</p>
          </div>

          <div class="flex items-center gap-2">
            <button class="btn" @click="goBack">Back</button>
            <IconActionButton :icon="Edit2" label="Edit" color="amber" :onClick="goEdit" />
            <IconActionButton :icon="Trash2" label="Delete" color="red" :onClick="openDeleteDialog" />
          </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-6">
          <div class="rounded-lg border bg-card p-4">
            <div class="text-sm text-muted-foreground">Model</div>
            <div class="mt-2 font-medium">{{ props.glueSpreader.model ?? '-' }}</div>
          </div>

          <div class="rounded-lg border bg-card p-4">
            <div class="text-sm text-muted-foreground">Glue (Kg)</div>
            <div class="mt-2 font-medium">{{ props.glueSpreader.glue_kg ?? '-' }}</div>
          </div>

          <div class="rounded-lg border bg-card p-4">
            <div class="text-sm text-muted-foreground">Hardener (Kg)</div>
            <div class="mt-2 font-medium">{{ props.glueSpreader.hardener_kg ?? '-' }}</div>
          </div>

          <div class="rounded-lg border bg-card p-4">
            <div class="text-sm text-muted-foreground">Powder (Kg)</div>
            <div class="mt-2 font-medium">{{ props.glueSpreader.powder_kg ?? '-' }}</div>
          </div>

          <div class="rounded-lg border bg-card p-4">
            <div class="text-sm text-muted-foreground">Colorant (Kg)</div>
            <div class="mt-2 font-medium">{{ props.glueSpreader.colorant_kg ?? '-' }}</div>
          </div>

          <div class="rounded-lg border bg-card p-4">
            <div class="text-sm text-muted-foreground">Anti-Termite (Kg)</div>
            <div class="mt-2 font-medium">{{ props.glueSpreader.anti_termite_kg ?? '-' }}</div>
          </div>

          <div class="rounded-lg border bg-card p-4">
            <div class="text-sm text-muted-foreground">Viscosity</div>
            <div class="mt-2 font-medium">{{ props.glueSpreader.viscosity ?? '-' }}</div>
          </div>

          <div class="rounded-lg border bg-card p-4">
            <div class="text-sm text-muted-foreground">Washes per Day</div>
            <div class="mt-2 font-medium">{{ props.glueSpreader.washes_per_day ?? '-' }}</div>
          </div>

          <div class="rounded-lg border bg-card p-4">
            <div class="text-sm text-muted-foreground">Glue Loss (Kg)</div>
            <div class="mt-2 font-medium">{{ props.glueSpreader.glue_loss_kg ?? '-' }}</div>
          </div>
        </div>

        <div class="mt-6 rounded-lg border bg-card p-4">
          <div class="text-sm text-muted-foreground">Notes</div>
          <div class="mt-2">{{ props.glueSpreader.notes ?? '-' }}</div>
        </div>
      </div>
    </div>

    <AlertDialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Confirm Delete Glue Spreader</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to delete "{{ props.glueSpreader.name }}"? This action cannot be undone.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <div class="flex justify-end gap-2">
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction @click="confirmDelete" :disabled="deleting" class="bg-red-600 hover:bg-red-700">Delete</AlertDialogAction>
        </div>
      </AlertDialogContent>
    </AlertDialog>

    <ToastNotifications />
  </AppLayout>
</template>
