<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { type BreadcrumbItem, type GlueSpreadersPageProps } from '@/types';
import IconActionButton from '@/components/ui/IconActionButton.vue';
import { Eye, Edit2, Trash2, Search } from 'lucide-vue-next';
import ToastNotifications from '@/components/ToastNotifications.vue';
import { useToast } from '@/composables/useToast';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useLocalization } from '@/composables/useLocalization';

const { t } = useLocalization();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  { title: t('data_management.data_management'), href: '/data-management/production' },
  { title: t('data_management.glue_spreaders'), href: '/data-management/glue-spreaders' },
]);

const props = defineProps<GlueSpreadersPageProps>();

const { success } = useToast();

const dataSource = computed(() => props.glueSpreaders?.data ?? []);
const loading = ref(false);
const search = ref(props.meta?.q ?? '');
let searchTimer: ReturnType<typeof setTimeout> | undefined;

const goCreate = () => router.get('/data-management/glue-spreaders/create');
const goShow = (id: number | string) => router.get(`/data-management/glue-spreaders/${id}`);
const goEdit = (id: number | string) => router.get(`/data-management/glue-spreaders/${id}/edit`);

const confirmDelete = (id: number | string, name: string) => {
  if (!window.confirm('Delete this glue spreader?')) return;
  router.visit(`/data-management/glue-spreaders/${id}`, {
    method: 'delete',
    onSuccess: () => {
      success('Glue Spreader deleted', `${name} has been deleted successfully`);
    },
  });
};

const goPrev = () => {
  const prev = props.glueSpreaders?.links?.prev;
  if (!prev) return;
  router.get(prev);
};

const goNext = () => {
  const next = props.glueSpreaders?.links?.next;
  if (!next) return;
  router.get(next);
};

const triggerSearch = () => {
  const perPage = props.meta?.per_page ?? 15;
  router.get('/data-management/glue-spreaders', { q: search.value || null, per_page: perPage }, { preserveState: true, replace: true });
};

// Watch for search changes with debounce
watch(search, () => {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(triggerSearch, 800);
});

router.on('start', () => (loading.value = true));
router.on('finish', () => (loading.value = false));

const formatDate = (val?: string | null) => {
  if (!val) return '-';
  const [datePart, timePart] = val.split(' ');
  if (!datePart) return val;
  const [year, month, day] = datePart.split('-');
  const [hour = '00', minute = '00'] = (timePart ?? '').split(':');
  return `${day}/${month}/${year} ${hour}:${minute}`;
};
</script>

<template>

  <Head :title="t('data_management.glue_spreaders')" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-semibold">{{ t('data_management.glue_spreaders') }}</h2>
          <p class="text-sm text-muted-foreground">{{ t('data_management.glue_spreaders_description') }}</p>
        </div>
        <button class="hover:cursor-pointer btn" @click="goCreate">{{ t('data_management.add_glue_spreader') }}</button>
      </div>

      <div class="mt-4">
        <div class="relative">
          <Search class="absolute left-2 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
          <Input v-model="search" :disabled="loading" type="search"
            :placeholder="t('data_management.search_placeholder')" class="pl-8" />
          <Spinner v-if="loading" class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground" />
        </div>
      </div>

      <div class="mt-4">

        <div class="overflow-x-auto">
          <table class="w-full table-auto border-collapse">
            <thead>
              <tr class="text-left">
                <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.id') }}</th>
                <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.name') }}</th>
                <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.model') }}</th>
                <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.glue_kg') }}</th>
                <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.hardener_kg') }}</th>
                <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.viscosity') }}</th>
                <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.created_at') }}</th>
                <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.created_by') }}</th>
                <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.updated_at') }}</th>
                <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.modified_by') }}</th>
                <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in dataSource" :key="row.id" class="border-t">
                <td class="px-3 py-2 text-sm">{{ row.id }}</td>
                <td class="px-3 py-2 text-sm">{{ row.name }}</td>
                <td class="px-3 py-2 text-sm">{{ row.model ?? '-' }}</td>
                <td class="px-3 py-2 text-sm">{{ row.glue_kg ?? '-' }}</td>
                <td class="px-3 py-2 text-sm">{{ row.hardener_kg ?? '-' }}</td>
                <td class="px-3 py-2 text-sm">{{ row.viscosity ?? '-' }}</td>
                <td class="px-3 py-2 text-sm">{{ formatDate(row.created_at) }}</td>
                <td class="px-3 py-2 text-sm">{{ row.created_by ?? '-' }}</td>
                <td class="px-3 py-2 text-sm">{{ formatDate(row.updated_at) }}</td>
                <td class="px-3 py-2 text-sm">{{ row.modified_by ?? '-' }}</td>
                <td class="px-3 py-2 text-sm">
                  <div class="flex items-center gap-4">
                    <IconActionButton :icon="Eye" :label="t('data_management.view')" color="blue" :onClick="() => goShow(row.id)" />
                    <IconActionButton :icon="Edit2" :label="t('data_management.edit')" color="amber" :onClick="() => goEdit(row.id)" />
                    <IconActionButton :icon="Trash2" :label="t('data_management.delete')" color="red"
                      :onClick="() => confirmDelete(row.id, row.name)" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex items-center justify-end gap-2">
          <button class="hover:cursor-pointer btn" :disabled="!props.glueSpreaders?.links?.prev || loading"
            @click="goPrev">{{ t('data_management.previous') }}</button>
          <button class="hover:cursor-pointer btn" :disabled="!props.glueSpreaders?.links?.next || loading"
            @click="goNext">{{ t('data_management.next') }}</button>
        </div>
      </div>
    </div>

    <ToastNotifications />
  </AppLayout>
</template>
