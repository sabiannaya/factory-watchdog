<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { type BreadcrumbItem, type ProductsPageProps } from '@/types';
import IconActionButton from '@/components/ui/IconActionButton.vue';
import { Eye, Edit2, Trash2 } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Data Management', href: '/data-management/production' },
    { title: 'Products', href: '/data-management/products' },
];

const props = defineProps<ProductsPageProps>();

const dataSource = props.products?.data ?? [];
const loading = ref(false);

const goCreate = () => router.get('/data-management/products/create');
const goShow = (id: number | string) => router.get(`/data-management/products/${id}`);
const goEdit = (id: number | string) => router.get(`/data-management/products/${id}/edit`);

const confirmDelete = (id: number | string) => {
  if (!window.confirm('Delete this product?')) return;
  router.visit(`/data-management/products/${id}`, { method: 'delete' });
};

const goPrev = () => {
  const prev = props.products?.links?.prev;
  if (!prev) return;
  router.get(prev);
};

const goNext = () => {
  const next = props.products?.links?.next;
  if (!next) return;
  router.get(next);
};

router.on('start', () => (loading.value = true));
router.on('finish', () => (loading.value = false));
</script>

<template>
  <Head title="Products" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <div class="rounded-xl border border-sidebar-border/70 p-6">
        <h2 class="text-lg font-semibold">Products (Setting Produk)</h2>
        <p class="mt-2 text-sm text-muted-foreground">Manage product definitions used by production.</p>

        <div class="mt-4">
          <div class="mb-4 flex items-center justify-between">
            <div></div>
            <button class="btn hover:cursor-pointer" @click="goCreate">Add Product</button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full table-auto border-collapse">
              <thead>
                <tr class="text-left">
                  <th class="px-3 py-2 text-sm font-medium">ID</th>
                  <th class="px-3 py-2 text-sm font-medium">Name</th>
                  <th class="px-3 py-2 text-sm font-medium">Thickness</th>
                  <th class="px-3 py-2 text-sm font-medium">Ply</th>
                  <th class="px-3 py-2 text-sm font-medium">Glue</th>
                  <th class="px-3 py-2 text-sm font-medium">Qty</th>
                  <th class="px-3 py-2 text-sm font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in dataSource" :key="row.id" class="border-t">
                  <td class="px-3 py-2 text-sm">{{ row.id }}</td>
                  <td class="px-3 py-2 text-sm">{{ row.name }}</td>
                  <td class="px-3 py-2 text-sm">{{ row.thickness }}</td>
                  <td class="px-3 py-2 text-sm">{{ row.ply }}</td>
                  <td class="px-3 py-2 text-sm">{{ row.glue_type }}</td>
                  <td class="px-3 py-2 text-sm">{{ row.qty }}</td>
                  <td class="px-3 py-2 text-sm">
                    <div class="flex items-center gap-4">
                      <IconActionButton :icon="Eye" label="Show" color="blue" :onClick="() => goShow(row.id)" />
                      <IconActionButton :icon="Edit2" label="Edit" color="amber" :onClick="() => goEdit(row.id)" />
                      <IconActionButton :icon="Trash2" label="Delete" color="red" :onClick="() => confirmDelete(row.id)" />
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-4 flex items-center justify-end gap-2">
            <button class="btn hover:cursor-pointer" :disabled="!props.products?.links?.prev || loading" @click="goPrev">Previous</button>
            <button class="btn hover:cursor-pointer" :disabled="!props.products?.links?.next || loading" @click="goNext">Next</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
