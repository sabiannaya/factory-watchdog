<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { type BreadcrumbItem, type GlueSpreaderFormErrors, type GlueSpreaderItem } from '@/types';
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
  { title: 'Edit', href: `/data-management/glue-spreaders/${props.glueSpreader.id}/edit` },
];

const form = ref({
  id: props.glueSpreader.id,
  name: props.glueSpreader.name,
  model: props.glueSpreader.model || '',
  capacity_ml: props.glueSpreader.capacity_ml ?? null,
  speed_mpm: props.glueSpreader.speed_mpm ?? null,
  status: props.glueSpreader.status || '',
  notes: props.glueSpreader.notes || '',
});
const errors = ref<GlueSpreaderFormErrors>({});
const submitting = ref(false);
const showConfirmDialog = ref(false);

const validate = (): boolean => {
  errors.value = {};
  const newErrors: GlueSpreaderFormErrors = {};

  if (!form.value.name?.trim()) newErrors.name = 'Name is required';
  if (form.value.capacity_ml !== null && form.value.capacity_ml < 0) newErrors.capacity_ml = 'Capacity must be 0 or greater';
  if (form.value.speed_mpm !== null && form.value.speed_mpm < 0) newErrors.speed_mpm = 'Speed must be 0 or greater';

  errors.value = newErrors;
  return Object.keys(newErrors).length === 0;
};

const handleSubmit = () => {
  if (validate()) showConfirmDialog.value = true;
};

const confirmSubmit = () => {
  submitting.value = true;
  router.put(`/data-management/glue-spreaders/${form.value.id}`, form.value, {
    onSuccess: () => {
      success('Glue Spreader updated', `${form.value.name} has been updated successfully`);
    },
    onFinish: () => (submitting.value = false),
  });
};
</script>

<template>
  <Head :title="`Edit Glue Spreader - ${props.glueSpreader.name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <div class="rounded-xl border border-sidebar-border/70 p-6">
        <h2 class="text-lg font-semibold">Edit Glue Spreader</h2>
        <p class="mt-2 text-sm text-muted-foreground">Update glue spreader details.</p>

        <div class="mt-4">
          <div class="grid grid-cols-2 gap-4">
            <label class="block">
              <div class="text-sm font-medium">Name <span class="text-red-500">*</span></div>
              <input v-model="form.name" type="text" placeholder="Enter name" class="input" :class="{ 'border-red-500': errors.name }" />
              <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name }}</p>
            </label>

            <label class="block">
              <div class="text-sm font-medium">Model</div>
              <input v-model="form.model" type="text" placeholder="Model identifier" class="input" :class="{ 'border-red-500': errors.model }" />
              <p v-if="errors.model" class="mt-1 text-xs text-red-500">{{ errors.model }}</p>
            </label>

            <label class="block">
              <div class="text-sm font-medium">Capacity (ml)</div>
              <input v-model.number="form.capacity_ml" type="number" placeholder="e.g., 1000" class="input" :class="{ 'border-red-500': errors.capacity_ml }" />
              <p v-if="errors.capacity_ml" class="mt-1 text-xs text-red-500">{{ errors.capacity_ml }}</p>
            </label>

            <label class="block">
              <div class="text-sm font-medium">Speed (mpm)</div>
              <input v-model.number="form.speed_mpm" type="number" placeholder="e.g., 10" class="input" :class="{ 'border-red-500': errors.speed_mpm }" />
              <p v-if="errors.speed_mpm" class="mt-1 text-xs text-red-500">{{ errors.speed_mpm }}</p>
            </label>

            <label class="block">
              <div class="text-sm font-medium">Status</div>
              <input v-model="form.status" type="text" placeholder="active / maintenance / retired" class="input" :class="{ 'border-red-500': errors.status }" />
              <p v-if="errors.status" class="mt-1 text-xs text-red-500">{{ errors.status }}</p>
            </label>

            <label class="block col-span-2">
              <div class="text-sm font-medium">Notes</div>
              <textarea v-model="form.notes" placeholder="Optional notes" class="input"></textarea>
            </label>
          </div>

          <div class="mt-4 flex justify-end">
            <button class="btn hover:cursor-pointer" :disabled="submitting" @click="handleSubmit">Save</button>
          </div>
        </div>
      </div>
    </div>

    <AlertDialog :open="showConfirmDialog" @update:open="showConfirmDialog = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Confirm Update Glue Spreader</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to save changes to this glue spreader?
          </AlertDialogDescription>
        </AlertDialogHeader>
        <div class="rounded-lg bg-muted/50 p-3">
          <div class="space-y-1 text-sm">
            <div><span class="font-medium">Name:</span> {{ form.name }}</div>
            <div><span class="font-medium">Model:</span> {{ form.model || '-' }}</div>
            <div><span class="font-medium">Capacity (ml):</span> {{ form.capacity_ml ?? '-' }}</div>
            <div><span class="font-medium">Speed (mpm):</span> {{ form.speed_mpm ?? '-' }}</div>
          </div>
        </div>
        <div class="flex justify-end gap-2">
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction @click="confirmSubmit" :disabled="submitting">Save</AlertDialogAction>
        </div>
      </AlertDialogContent>
    </AlertDialog>

    <ToastNotifications />
  </AppLayout>
</template>
