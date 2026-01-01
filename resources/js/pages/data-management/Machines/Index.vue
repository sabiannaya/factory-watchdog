<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref, watch, computed } from 'vue';
import { Eye, Edit2, Trash2, Search } from 'lucide-vue-next';
import IconActionButton from '@/components/ui/IconActionButton.vue';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useLocalization } from '@/composables/useLocalization';

const { t } = useLocalization();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: t('data_management.data_management'),
        href: '/data-management/production',
    },
    {
        title: t('data_management.machines'),
        href: '/data-management/machine',
    },
]);

const loading = ref(false);

const props = defineProps<{ machineGroups?: { data: any[]; next_cursor?: string | null; prev_cursor?: string | null }, meta?: { sort?: string; direction?: string; q?: string; per_page?: number } }>();

const dataSource = computed(() => props.machineGroups?.data ?? []);
const search = ref(props.meta?.q ?? '');
let searchTimer: ReturnType<typeof setTimeout> | undefined;

const onServerSearch = (q: string) => {
    const perPage = props.meta?.per_page ?? 10;
    const sort = props.meta?.sort ?? 'name';
    const direction = props.meta?.direction ?? 'asc';
    router.get(window.location.pathname, { q, per_page: perPage, sort, direction }, { preserveState: true, replace: true });
};

const onServerSort = ({ sort, direction }: { sort?: string; direction?: string }) => {
    const perPage = props.meta?.per_page ?? 10;
    const q = props.meta?.q ?? '';
    if (!sort) return;
    router.get(window.location.pathname, { sort, direction: direction ?? 'asc', per_page: perPage, q }, { preserveState: true, replace: true });
};

const goNext = () => {
    const next = props.machineGroups?.next_cursor;
    if (!next) return;
    const perPage = props.meta?.per_page ?? 10;
    const sort = props.meta?.sort ?? 'name';
    const direction = props.meta?.direction ?? 'asc';
    router.get(window.location.pathname, { cursor: next, q: search.value || null, per_page: perPage, sort, direction }, { preserveState: true, replace: true });
};

const goDetail = (id: number | string) => {
    router.get(`/data-management/machine/${id}`);
};

const goEdit = (id: number | string) => {
    router.get(`/data-management/machine/${id}/edit`);
};

const confirmDelete = (id: number | string) => {
    // simple browser confirm
    // eslint-disable-next-line no-alert
    if (!window.confirm(t('data_management.confirm_delete_machine'))) return;
    router.visit(`/data-management/machine/${id}`, { method: 'delete', preserveState: false });
};

const goPrev = () => {
    const prev = props.machineGroups?.prev_cursor;
    if (!prev) return;
    const perPage = props.meta?.per_page ?? 10;
    const sort = props.meta?.sort ?? 'name';
    const direction = props.meta?.direction ?? 'asc';
    router.get(window.location.pathname, { cursor: prev, q: search.value || null, per_page: perPage, sort, direction }, { preserveState: true, replace: true });
};

const triggerSearch = () => {
    const perPage = props.meta?.per_page ?? 10;
    const sort = props.meta?.sort ?? 'name';
    const direction = props.meta?.direction ?? 'asc';
    router.get(window.location.pathname, { q: search.value || null, per_page: perPage, sort, direction }, { preserveState: true, replace: true });
};

// Watch for search changes with debounce
watch(search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(triggerSearch, 800);
});

// Router (Inertia) loading
router.on('start', () => (loading.value = true));
router.on('finish', () => (loading.value = false));

</script>

<template>

    <Head :title="t('data_management.machines')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold">{{ t('data_management.machines') }}</h2>
                    <p class="text-sm text-muted-foreground">{{ t('data_management.manage_machines') }}</p>
                </div>
                <button class="hover:cursor-pointer btn" @click="router.get('/data-management/machine/create')">
                    {{ t('data_management.create_machine') }}
                </button>
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
                                <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.total') }}</th>
                                <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.active') }}</th>
                                <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.inactive') }}</th>
                                <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.created') }}</th>
                                <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in dataSource" :key="row.machine_group_id" class="border-t">
                                <td class="px-3 py-2 text-sm">{{ row.machine_group_id }}</td>
                                <td class="px-3 py-2 text-sm">{{ row.name }}</td>
                                <td class="px-3 py-2 text-sm">{{ row.total_machines ?? 0 }}</td>
                                <td class="px-3 py-2 text-sm">{{ row.active_machines ?? 0 }}</td>
                                <td class="px-3 py-2 text-sm">{{ row.inactive_machines ?? 0 }}</td>
                                <td class="px-3 py-2 text-sm">{{ row.created_at }}</td>
                                <td class="px-3 py-2 text-sm">
                                    <div class="flex items-center gap-4">
                                        <IconActionButton :icon="Eye" :label="t('data_management.detail')" color="blue"
                                            :onClick="() => goDetail(row.machine_group_id)" />
                                        <IconActionButton :icon="Edit2" :label="t('data_management.edit')" color="amber"
                                            :onClick="() => goEdit(row.machine_group_id)" />
                                        <IconActionButton :icon="Trash2" :label="t('data_management.delete')" color="red"
                                            :onClick="() => confirmDelete(row.machine_group_id)" />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex items-center justify-end gap-2">
                    <button class="hover:cursor-pointer btn" :disabled="!props.machineGroups?.prev_cursor || loading"
                        @click="goPrev">{{ t('data_management.previous') }}</button>
                    <button class="hover:cursor-pointer btn" :disabled="!props.machineGroups?.next_cursor || loading"
                        @click="goNext">{{ t('data_management.next') }}</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
