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
  glue_kg: props.glueSpreader.glue_kg ?? null,
  hardener_kg: props.glueSpreader.hardener_kg ?? null,
  powder_kg: props.glueSpreader.powder_kg ?? null,
  colorant_kg: props.glueSpreader.colorant_kg ?? null,
  anti_termite_kg: props.glueSpreader.anti_termite_kg ?? null,
  viscosity: props.glueSpreader.viscosity || '',
  washes_per_day: props.glueSpreader.washes_per_day ?? null,
  glue_loss_kg: props.glueSpreader.glue_loss_kg ?? null,
  notes: props.glueSpreader.notes || '',
});
const errors = ref<GlueSpreaderFormErrors>({});
const submitting = ref(false);
const showConfirmDialog = ref(false);

const validate = (): boolean => {
  errors.value = {};
  const newErrors: GlueSpreaderFormErrors = {};

  if (!form.value.name?.trim()) newErrors.name = 'Name is required';
  if (!form.value.model?.trim()) newErrors.model = 'Model is required';
  if (form.value.glue_kg === null || form.value.glue_kg === undefined) {
    newErrors.glue_kg = 'Glue (Kg) is required';
  } else if (form.value.glue_kg < 0) {
    newErrors.glue_kg = 'Glue must be 0 or greater';
  }
  if (form.value.hardener_kg === null || form.value.hardener_kg === undefined) {
    newErrors.hardener_kg = 'Hardener (Kg) is required';
  } else if (form.value.hardener_kg < 0) {
    newErrors.hardener_kg = 'Hardener must be 0 or greater';
  }
  if (form.value.powder_kg === null || form.value.powder_kg === undefined) {
    newErrors.powder_kg = 'Powder (Kg) is required';
  } else if (form.value.powder_kg < 0) {
    newErrors.powder_kg = 'Powder must be 0 or greater';
  }
  if (form.value.colorant_kg === null || form.value.colorant_kg === undefined) {
    newErrors.colorant_kg = 'Colorant (Kg) is required';
  } else if (form.value.colorant_kg < 0) {
    newErrors.colorant_kg = 'Colorant must be 0 or greater';
  }
  if (form.value.anti_termite_kg === null || form.value.anti_termite_kg === undefined) {
    newErrors.anti_termite_kg = 'Anti-termite (Kg) is required';
  } else if (form.value.anti_termite_kg < 0) {
    newErrors.anti_termite_kg = 'Anti-termite must be 0 or greater';
  }
  if (!form.value.viscosity?.trim()) newErrors.viscosity = 'Viscosity is required';
  if (form.value.washes_per_day === null || form.value.washes_per_day === undefined) {
    newErrors.washes_per_day = 'Washes per day is required';
  } else if (form.value.washes_per_day < 0) {
    newErrors.washes_per_day = 'Washes must be 0 or greater';
  }
  if (form.value.glue_loss_kg === null || form.value.glue_loss_kg === undefined) {
    newErrors.glue_loss_kg = 'Glue loss (Kg) is required';
  } else if (form.value.glue_loss_kg < 0) {
    newErrors.glue_loss_kg = 'Loss must be 0 or greater';
  }

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
              <div class="text-sm font-medium">Model <span class="text-red-500">*</span></div>
              <input v-model="form.model" type="text" placeholder="Model identifier" class="input" :class="{ 'border-red-500': errors.model }" />
              <p v-if="errors.model" class="mt-1 text-xs text-red-500">{{ errors.model }}</p>
            </label>

            <label class="block">
              <div class="text-sm font-medium">Glue (Kg) <span class="text-red-500">*</span></div>
              <input v-model.number="form.glue_kg" type="number" step="0.01" placeholder="e.g., 120.5" class="input" :class="{ 'border-red-500': errors.glue_kg }" />
              <p v-if="errors.glue_kg" class="mt-1 text-xs text-red-500">{{ errors.glue_kg }}</p>
            </label>

            <label class="block">
              <div class="text-sm font-medium">Hardener (Kg) <span class="text-red-500">*</span></div>
              <input v-model.number="form.hardener_kg" type="number" step="0.01" placeholder="e.g., 10.25" class="input" :class="{ 'border-red-500': errors.hardener_kg }" />
              <p v-if="errors.hardener_kg" class="mt-1 text-xs text-red-500">{{ errors.hardener_kg }}</p>
            </label>

            <label class="block">
              <div class="text-sm font-medium">Powder (Kg) <span class="text-red-500">*</span></div>
              <input v-model.number="form.powder_kg" type="number" step="0.01" placeholder="e.g., 50.0" class="input" :class="{ 'border-red-500': errors.powder_kg }" />
              <p v-if="errors.powder_kg" class="mt-1 text-xs text-red-500">{{ errors.powder_kg }}</p>
            </label>

            <label class="block">
              <div class="text-sm font-medium">Colorant (Kg) <span class="text-red-500">*</span></div>
              <input v-model.number="form.colorant_kg" type="number" step="0.01" placeholder="e.g., 2.5" class="input" :class="{ 'border-red-500': errors.colorant_kg }" />
              <p v-if="errors.colorant_kg" class="mt-1 text-xs text-red-500">{{ errors.colorant_kg }}</p>
            </label>

            <label class="block">
              <div class="text-sm font-medium">Anti-Termite (Kg) <span class="text-red-500">*</span></div>
              <input v-model.number="form.anti_termite_kg" type="number" step="0.01" placeholder="e.g., 0.5" class="input" :class="{ 'border-red-500': errors.anti_termite_kg }" />
              <p v-if="errors.anti_termite_kg" class="mt-1 text-xs text-red-500">{{ errors.anti_termite_kg }}</p>
            </label>

            <label class="block">
              <div class="text-sm font-medium">Viscosity <span class="text-red-500">*</span></div>
              <input v-model="form.viscosity" type="text" placeholder="e.g., medium" class="input" :class="{ 'border-red-500': errors.viscosity }" />
              <p v-if="errors.viscosity" class="mt-1 text-xs text-red-500">{{ errors.viscosity }}</p>
            </label>

            <label class="block">
              <div class="text-sm font-medium">Washes per Day <span class="text-red-500">*</span></div>
              <input v-model.number="form.washes_per_day" type="number" placeholder="e.g., 3" class="input" :class="{ 'border-red-500': errors.washes_per_day }" />
              <p v-if="errors.washes_per_day" class="mt-1 text-xs text-red-500">{{ errors.washes_per_day }}</p>
            </label>

            <label class="block">
              <div class="text-sm font-medium">Glue Loss (Kg) <span class="text-red-500">*</span></div>
              <input v-model.number="form.glue_loss_kg" type="number" step="0.01" placeholder="e.g., 1.2" class="input" :class="{ 'border-red-500': errors.glue_loss_kg }" />
              <p v-if="errors.glue_loss_kg" class="mt-1 text-xs text-red-500">{{ errors.glue_loss_kg }}</p>
            </label>

            <label class="block col-span-2">
              <div class="text-sm font-medium">Notes</div>
              <textarea v-model="form.notes" placeholder="Optional notes" class="input"></textarea>
            </label>
          </div>

          <div class="mt-4 flex justify-end">
            <button class="btn" :disabled="submitting" @click="handleSubmit">Save</button>
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
            <div><span class="font-medium">Glue (Kg):</span> {{ form.glue_kg ?? '-' }}</div>
            <div><span class="font-medium">Hardener (Kg):</span> {{ form.hardener_kg ?? '-' }}</div>
            <div><span class="font-medium">Powder (Kg):</span> {{ form.powder_kg ?? '-' }}</div>
            <div><span class="font-medium">Colorant (Kg):</span> {{ form.colorant_kg ?? '-' }}</div>
            <div><span class="font-medium">Anti-Termite (Kg):</span> {{ form.anti_termite_kg ?? '-' }}</div>
            <div><span class="font-medium">Viscosity:</span> {{ form.viscosity || '-' }}</div>
            <div><span class="font-medium">Washes per Day:</span> {{ form.washes_per_day ?? '-' }}</div>
            <div><span class="font-medium">Glue Loss (Kg):</span> {{ form.glue_loss_kg ?? '-' }}</div>
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
