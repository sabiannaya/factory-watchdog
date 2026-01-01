<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { type BreadcrumbItem, type ProductsPageProps } from '@/types';
import { useLocalization } from '@/composables/useLocalization';
import IconActionButton from '@/components/ui/IconActionButton.vue';
import { Eye, Edit2, Trash2, Search } from 'lucide-vue-next';
import ToastNotifications from '@/components/ToastNotifications.vue';
import { useToast } from '@/composables/useToast';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

const props = defineProps<ProductsPageProps>();

const { t } = useLocalization();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('data_management.data_management'), href: '/data-management/production' },
    { title: t('data_management.products'), href: '/data-management/products' },
]);

const { success } = useToast();

const dataSource = computed(() => props.products?.data ?? []);
const loading = ref(false);
const search = ref(props.meta?.q ?? '');
let searchTimer: ReturnType<typeof setTimeout> | undefined;

const triggerSearch = () => {
  const perPage = props.meta?.per_page ?? 15;
  router.get('/data-management/products', { q: search.value || null, per_page: perPage }, { preserveState: true, replace: true });
};

// Watch for search changes with debounce
watch(search, () => {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(triggerSearch, 800);
});

const goCreate = () => router.get('/data-management/products/create');
const goShow = (id: number | string) => router.get(`/data-management/products/${id}`);
const goEdit = (id: number | string) => router.get(`/data-management/products/${id}/edit`);

const confirmDelete = (id: number | string, name: string) => {
  if (!window.confirm(t('data_management.confirm_delete_product'))) return;
  router.visit(`/data-management/products/${id}`, { 
    method: 'delete',
    onSuccess: () => {
      success('Product deleted', `${name} has been deleted successfully`);
    },
  });
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
  <Head :title="t('data_management.products')" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-2xl font-semibold">{{ t('data_management.products') }}</h2>
            <p class="text-sm text-muted-foreground">{{ t('data_management.products_description') }}</p>
          </div>
          <button class="hover:cursor-pointer btn" @click="goCreate">{{ t('data_management.add_product') }}</button>
        </div>

        <div class="mt-4">
          <div class="relative">
            <Search class="absolute left-2 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              v-model="search"
              :disabled="loading"
              type="search"
              :placeholder="t('data_management.search_placeholder')"
              class="pl-8"
            />
            <Spinner v-if="loading" class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground" />
          </div>
        </div>

        <div class="mt-4">

          <div class="overflow-x-auto">
            <table class="w-full table-auto border-collapse">
              <thead>
                <tr class="text-left">
                  <th class="px-3 py-2 text-sm font-medium">ID</th>
                  <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.name') }}</th>
                  <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.thickness') }}</th>
                  <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.ply') }}</th>
                  <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.glue') }}</th>
                  <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.qty') }}</th>
                  <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.actions') }}</th>
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
                      <IconActionButton :icon="Trash2" label="Delete" color="red" :onClick="() => confirmDelete(row.id, row.name)" />
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-4 flex items-center justify-end gap-2">
            <button class="hover:cursor-pointer btn" :disabled="!props.products?.links?.prev || loading" @click="goPrev">{{ t('data_management.previous') }}</button>
            <button class="hover:cursor-pointer btn" :disabled="!props.products?.links?.next || loading" @click="goNext">{{ t('data_management.next') }}</button>
          </div>
      </div>
    </div>

    <ToastNotifications />
  </AppLayout>
</template>
