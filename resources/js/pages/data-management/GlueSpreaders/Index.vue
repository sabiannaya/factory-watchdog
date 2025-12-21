<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { type BreadcrumbItem, type GlueSpreadersPageProps } from '@/types';
import IconActionButton from '@/components/ui/IconActionButton.vue';
import { Eye, Edit2, Trash2 } from 'lucide-vue-next';
import ToastNotifications from '@/components/ToastNotifications.vue';
import { useToast } from '@/composables/useToast';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Data Management', href: '/data-management/production' },
  { title: 'Glue Spreaders', href: '/data-management/glue-spreaders' },
];

const props = defineProps<GlueSpreadersPageProps>();

const { success } = useToast();

const dataSource = props.glueSpreaders?.data ?? [];
const loading = ref(false);

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

router.on('start', () => (loading.value = true));
router.on('finish', () => (loading.value = false));
</script>

<template>
  <Head title="Glue Spreaders" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <div class="rounded-xl border border-sidebar-border/70 p-6">
        <h2 class="text-lg font-semibold">Glue Spreaders</h2>
        <p class="mt-2 text-sm text-muted-foreground">Manage glue spreader equipment used in production.</p>

        <div class="mt-4">
          <div class="mb-4 flex items-center justify-between">
            <div></div>
            <button class="btn hover:cursor-pointer" @click="goCreate">Add Glue Spreader</button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full table-auto border-collapse">
              <thead>
                <tr class="text-left">
                  <th class="px-3 py-2 text-sm font-medium">ID</th>
                  <th class="px-3 py-2 text-sm font-medium">Name</th>
                  <th class="px-3 py-2 text-sm font-medium">Model</th>
                  <th class="px-3 py-2 text-sm font-medium">Capacity (ml)</th>
                  <th class="px-3 py-2 text-sm font-medium">Speed (mpm)</th>
                  <th class="px-3 py-2 text-sm font-medium">Status</th>
                  <th class="px-3 py-2 text-sm font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in dataSource" :key="row.id" class="border-t">
                  <td class="px-3 py-2 text-sm">{{ row.id }}</td>
                  <td class="px-3 py-2 text-sm">{{ row.name }}</td>
                  <td class="px-3 py-2 text-sm">{{ row.model ?? '-' }}</td>
                  <td class="px-3 py-2 text-sm">{{ row.capacity_ml ?? '-' }}</td>
                  <td class="px-3 py-2 text-sm">{{ row.speed_mpm ?? '-' }}</td>
                  <td class="px-3 py-2 text-sm">{{ row.status ?? '-' }}</td>
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
            <button class="btn hover:cursor-pointer" :disabled="!props.glueSpreaders?.links?.prev || loading" @click="goPrev">Previous</button>
            <button class="btn hover:cursor-pointer" :disabled="!props.glueSpreaders?.links?.next || loading" @click="goNext">Next</button>
          </div>
        </div>
      </div>
    </div>

    <ToastNotifications />
  </AppLayout>
</template>
