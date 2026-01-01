<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import IconActionButton from '@/components/ui/IconActionButton.vue';
import { Eye, Edit2, Trash2, Search } from 'lucide-vue-next';
import ToastNotifications from '@/components/ToastNotifications.vue';
import { useToast } from '@/composables/useToast';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useLocalization } from '@/composables/useLocalization';

// Local types for this page
interface BreadcrumbItem { title: string; href: string }

interface WarehouseRow {
  id: number;
  source?: string | null;
  quantity?: number | null;
  packing?: string | null;
  notes?: string | null;
  created_at?: string | null;
  created_by?: string | null;
  updated_at?: string | null;
  modified_by?: string | null;
}

interface WarehousesPageProps {
  warehouses?: { data?: WarehouseRow[]; links?: { prev?: string | null; next?: string | null } };
  meta?: { q?: string | null; per_page?: number | null };
}

const { t } = useLocalization();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  { title: t('data_management.data_management'), href: '/data-management/production' },
  { title: t('data_management.warehouse'), href: '/data-management/warehouses' },
]);

const props = defineProps<WarehousesPageProps>();

const { success } = useToast();

const dataSource = computed(() => props.warehouses?.data ?? []);
const loading = ref(false);
const search = ref(props.meta?.q ?? '');
let searchTimer: ReturnType<typeof setTimeout> | undefined;

const goCreate = () => router.get('/data-management/warehouses/create');
const goShow = (id: number | string) => router.get(`/data-management/warehouses/${id}`);
const goEdit = (id: number | string) => router.get(`/data-management/warehouses/${id}/edit`);

const confirmDelete = (id: number | string, name: string) => {
  if (!window.confirm('Delete this warehouse record?')) return;
  router.visit(`/data-management/warehouses/${id}`, {
    method: 'delete',
    onSuccess: () => {
      success('Warehouse deleted', `${name} has been deleted successfully`);
    },
  });
};

const goPrev = () => {
  const prev = props.warehouses?.links?.prev;
  if (!prev) return;
  router.get(prev);
};

const goNext = () => {
  const next = props.warehouses?.links?.next;
  if (!next) return;
  router.get(next);
};

const triggerSearch = () => {
  const perPage = props.meta?.per_page ?? 15;
  router.get('/data-management/warehouses', { q: search.value || null, per_page: perPage }, { preserveState: true, replace: true });
};

watch(search, () => {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(triggerSearch, 800);
});

router.on('start', () => (loading.value = true));
router.on('finish', () => (loading.value = false));

const formatDate = (val?: string | null) => {
  if (!val) return '-';
  // Expecting format YYYY-MM-DD HH:MM:SS
  const [datePart, timePart] = val.split(' ');
  if (!datePart) return val;
  const [year, month, day] = datePart.split('-');
  const [hour = '00', minute = '00'] = (timePart ?? '').split(':');
  return `${day}/${month}/${year} ${hour}:${minute}`;
};
</script>

<template>

  <Head title="Warehouse" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-semibold">Warehouse</h2>
          <p class="text-sm text-muted-foreground">Manage warehouse receipts from production.</p>
        </div>
        <button class="hover:cursor-pointer btn" @click="goCreate">Add Record</button>
      </div>

      <div class="mt-4">
        <div class="relative">
          <Search class="absolute left-2 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
          <Input v-model="search" :disabled="loading" type="search"
            placeholder="Search warehouses..." class="pl-8" />
          <Spinner v-if="loading" class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground" />
        </div>
      </div>

      <div class="mt-4">

        <div class="overflow-x-auto">
          <table class="w-full table-auto border-collapse">
            <thead>
              <tr class="text-left">
                <th class="px-3 py-2 text-sm font-medium">ID</th>
                <th class="px-3 py-2 text-sm font-medium">Source</th>
                <th class="px-3 py-2 text-sm font-medium">Quantity</th>
                <th class="px-3 py-2 text-sm font-medium">Packing</th>
                <th class="px-3 py-2 text-sm font-medium">Notes</th>
                <th class="px-3 py-2 text-sm font-medium">Created At</th>
                <th class="px-3 py-2 text-sm font-medium">Created By</th>
                <th class="px-3 py-2 text-sm font-medium">Updated At</th>
                <th class="px-3 py-2 text-sm font-medium">Modified By</th>
                <th class="px-3 py-2 text-sm font-medium">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in dataSource" :key="row.id" class="border-t">
                <td class="px-3 py-2 text-sm">{{ row.id }}</td>
                <td class="px-3 py-2 text-sm">{{ row.source ?? '-' }}</td>
                <td class="px-3 py-2 text-sm">{{ row.quantity ?? '-' }}</td>
                <td class="px-3 py-2 text-sm">{{ row.packing ?? '-' }}</td>
                <td class="px-3 py-2 text-sm">{{ row.notes ?? '-' }}</td>
                <td class="px-3 py-2 text-sm">{{ formatDate(row.created_at) }}</td>
                <td class="px-3 py-2 text-sm">{{ row.created_by ?? '-' }}</td>
                <td class="px-3 py-2 text-sm">{{ formatDate(row.updated_at) }}</td>
                <td class="px-3 py-2 text-sm">{{ row.modified_by ?? '-' }}</td>
                <td class="px-3 py-2 text-sm">
                  <div class="flex items-center gap-4">
                    <IconActionButton :icon="Eye" label="Show" color="blue" :onClick="() => goShow(row.id)" />
                    <IconActionButton :icon="Edit2" label="Edit" color="amber" :onClick="() => goEdit(row.id)" />
                    <IconActionButton :icon="Trash2" label="Delete" color="red"
                      :onClick="() => confirmDelete(row.id, String(row.source ?? row.id))" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex items-center justify-end gap-2">
          <button class="hover:cursor-pointer btn" :disabled="!props.warehouses?.links?.prev || loading"
            @click="goPrev">Previous</button>
          <button class="hover:cursor-pointer btn" :disabled="!props.warehouses?.links?.next || loading"
            @click="goNext">Next</button>
        </div>
      </div>
    </div>

    <ToastNotifications />
  </AppLayout>
</template>
