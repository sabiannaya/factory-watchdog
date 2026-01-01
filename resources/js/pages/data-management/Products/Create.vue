<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { type BreadcrumbItem, type ProductFormData, type ProductFormErrors } from '@/types';
import { useLocalization } from '@/composables/useLocalization';
import AlertDialog from '@/components/ui/alert-dialog/AlertDialog.vue';
import AlertDialogAction from '@/components/ui/alert-dialog/AlertDialogAction.vue';
import AlertDialogCancel from '@/components/ui/alert-dialog/AlertDialogCancel.vue';
import AlertDialogContent from '@/components/ui/alert-dialog/AlertDialogContent.vue';
import AlertDialogDescription from '@/components/ui/alert-dialog/AlertDialogDescription.vue';
import AlertDialogHeader from '@/components/ui/alert-dialog/AlertDialogHeader.vue';
import AlertDialogTitle from '@/components/ui/alert-dialog/AlertDialogTitle.vue';
import ToastNotifications from '@/components/ToastNotifications.vue';
import { useToast } from '@/composables/useToast';

const { success } = useToast();
const { t } = useLocalization();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('data_management.data_management'), href: '/data-management/production' },
    { title: t('data_management.products'), href: '/data-management/products' },
    { title: t('data_management.create'), href: '/data-management/products/create' },
]);

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
        onSuccess: () => {
            success('Product created', `${form.value.name} has been created successfully`);
        },
        onFinish: () => (submitting.value = false),
    });
};
</script>

<template>
  <Head :title="t('data_management.create_product')" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <div class="rounded-xl border border-sidebar-border/70 p-6">
        <h2 class="text-lg font-semibold">{{ t('data_management.create_product') }}</h2>
        <p class="mt-2 text-sm text-muted-foreground">{{ t('data_management.create_product_description') }}</p>

        <div class="mt-4">
          <div class="grid grid-cols-2 gap-4">
            <label class="block">
              <div class="text-sm font-medium">{{ t('data_management.name') }} <span class="text-red-500">*</span></div>
              <input v-model="form.name" type="text" :placeholder="t('data_management.enter_product_name')" class="input" :class="{ 'border-red-500': errors.name }" />
              <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name }}</p>
            </label>

            <label class="block">
              <div class="text-sm font-medium">{{ t('data_management.thickness') }} <span class="text-red-500">*</span></div>
              <input v-model="form.thickness" type="text" :placeholder="t('data_management.thickness_placeholder')" class="input" :class="{ 'border-red-500': errors.thickness }" />
              <p v-if="errors.thickness" class="mt-1 text-xs text-red-500">{{ errors.thickness }}</p>
            </label>

            <label class="block">
              <div class="text-sm font-medium">{{ t('data_management.ply') }} <span class="text-red-500">*</span></div>
              <input v-model="form.ply" type="text" :placeholder="t('data_management.ply_placeholder')" class="input" :class="{ 'border-red-500': errors.ply }" />
              <p v-if="errors.ply" class="mt-1 text-xs text-red-500">{{ errors.ply }}</p>
            </label>

            <label class="block">
              <div class="text-sm font-medium">{{ t('data_management.glue_type') }} <span class="text-red-500">*</span></div>
              <input v-model="form.glue_type" type="text" :placeholder="t('data_management.glue_type_placeholder')" class="input" :class="{ 'border-red-500': errors.glue_type }" />
              <p v-if="errors.glue_type" class="mt-1 text-xs text-red-500">{{ errors.glue_type }}</p>
            </label>

            <label class="block">
              <div class="text-sm font-medium">{{ t('data_management.qty') }} <span class="text-red-500">*</span></div>
              <input v-model.number="form.qty" type="number" placeholder="0" class="input" :class="{ 'border-red-500': errors.qty }" />
              <p v-if="errors.qty" class="mt-1 text-xs text-red-500">{{ errors.qty }}</p>
            </label>

            <label class="block col-span-2">
              <div class="text-sm font-medium">{{ t('data_management.notes') }}</div>
              <textarea v-model="form.notes" :placeholder="t('data_management.notes_placeholder')" class="input"></textarea>
            </label>
          </div>

          <div class="mt-4 flex justify-end">
            <button class="hover:cursor-pointer btn" :disabled="submitting" @click="handleSubmit">{{ t('data_management.create') }}</button>
          </div>
        </div>
      </div>
    </div>

    <AlertDialog :open="showConfirmDialog" @update:open="showConfirmDialog = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{{ t('data_management.confirm_create_product') }}</AlertDialogTitle>
          <AlertDialogDescription>
            {{ t('data_management.confirm_create_product_description') }}
          </AlertDialogDescription>
        </AlertDialogHeader>
        <div class="rounded-lg bg-muted/50 p-3">
          <div class="space-y-1 text-sm">
            <div><span class="font-medium">{{ t('data_management.name') }}:</span> {{ form.name }}</div>
            <div><span class="font-medium">{{ t('data_management.thickness') }}:</span> {{ form.thickness }}</div>
            <div><span class="font-medium">{{ t('data_management.ply') }}:</span> {{ form.ply }}</div>
            <div><span class="font-medium">{{ t('data_management.glue_type') }}:</span> {{ form.glue_type }}</div>
            <div><span class="font-medium">{{ t('data_management.qty') }}:</span> {{ form.qty }}</div>
          </div>
        </div>
        <div class="flex justify-end gap-2">
          <AlertDialogCancel>{{ t('data_management.cancel') }}</AlertDialogCancel>
          <AlertDialogAction @click="confirmSubmit" :disabled="submitting">{{ t('data_management.create') }}</AlertDialogAction>
        </div>
      </AlertDialogContent>
    </AlertDialog>

    <ToastNotifications />
  </AppLayout>
</template>
