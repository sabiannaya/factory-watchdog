<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type ProductItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
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

const props = defineProps<{ product: ProductItem }>();

const { success } = useToast();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Data Management', href: '/data-management/production' },
    { title: 'Products', href: '/data-management/products' },
    {
        title: props.product.name,
        href: `/data-management/products/${props.product.id}`,
    },
];

const showDeleteDialog = ref(false);
const deleting = ref(false);

const goBack = () => router.get('/data-management/products');
const goEdit = () =>
    router.get(`/data-management/products/${props.product.id}/edit`);

const openDeleteDialog = () => {
    showDeleteDialog.value = true;
};

const confirmDelete = () => {
    deleting.value = true;
    router.visit(`/data-management/products/${props.product.id}`, {
        method: 'delete',
        onSuccess: () => {
            success('Product deleted', `${props.product.name} has been deleted successfully`);
        },
        onFinish: () => (deleting.value = false),
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Product — ${props.product.name}`" />
        <div class="p-4">
            <div class="rounded-xl border border-sidebar-border/70 p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">
                            {{ props.product.name }}
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Product details (Setting Produk)
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <IconActionButton :icon="ArrowLeft" label="Back" :onClick="goBack" />
                        <IconActionButton :icon="Edit2" label="Edit" color="amber" :onClick="goEdit" />
                        <IconActionButton :icon="Trash2" label="Delete" color="red" :onClick="openDeleteDialog" />
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-6">
                    <div class="rounded-lg border bg-card p-4">
                        <div class="text-sm text-muted-foreground">
                            Thickness
                        </div>
                        <div class="mt-2 font-medium">
                            {{ props.product.thickness ?? '-' }}
                        </div>
                    </div>

                    <div class="rounded-lg border bg-card p-4">
                        <div class="text-sm text-muted-foreground">Ply</div>
                        <div class="mt-2 font-medium">
                            {{ props.product.ply ?? '-' }}
                        </div>
                    </div>

                    <div class="rounded-lg border bg-card p-4">
                        <div class="text-sm text-muted-foreground">
                            Glue Type
                        </div>
                        <div class="mt-2 font-medium">
                            {{ props.product.glue_type ?? '-' }}
                        </div>
                    </div>

                    <div class="rounded-lg border bg-card p-4">
                        <div class="text-sm text-muted-foreground">Qty</div>
                        <div class="mt-2 font-medium">
                            {{ props.product.qty }}
                        </div>
                    </div>
                </div>

                <div class="mt-6 rounded-lg border bg-card p-4">
                    <div class="text-sm text-muted-foreground">Notes</div>
                    <div class="mt-2">{{ props.product.notes ?? '-' }}</div>
                </div>
            </div>
        </div>

        <AlertDialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Confirm Delete Product</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to delete "{{ props.product.name }}"? This action cannot be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <div class="flex justify-end gap-2">
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="confirmDelete" :disabled="deleting" class="bg-red-600 hover:bg-red-700">
                        Delete
                    </AlertDialogAction>
                </div>
            </AlertDialogContent>
        </AlertDialog>

        <ToastNotifications />
    </AppLayout>
</template>
