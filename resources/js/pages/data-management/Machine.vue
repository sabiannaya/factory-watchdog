<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Eye, Edit2, Trash2 } from 'lucide-vue-next';
import IconActionButton from '@/components/ui/IconActionButton.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Data Management',
        href: '/data-management/production',
    },
    {
        title: 'Machine',
        href: '/data-management/machine',
    },
];

const loading = ref(false);

const props = defineProps<{ machineGroups?: { data: any[]; next_cursor?: string | null; prev_cursor?: string | null }, meta?: { sort?: string; direction?: string; q?: string; per_page?: number } }>();

const dataSource = props.machineGroups?.data ?? [];

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
    router.get(window.location.pathname, { cursor: next }, { preserveState: true, replace: true });
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
    if (!window.confirm('Are you sure you want to delete this machine group?')) return;
    router.visit(`/data-management/machine/${id}`, { method: 'delete', preserveState: false });
};

const goPrev = () => {
    const prev = props.machineGroups?.prev_cursor;
    if (!prev) return;
    router.get(window.location.pathname, { cursor: prev }, { preserveState: true, replace: true });
};

// Router (Inertia) loading
router.on('start', () => (loading.value = true));
router.on('finish', () => (loading.value = false));

</script>

<template>
    <Head title="Machine" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="rounded-xl border border-sidebar-border/70 p-6">
                <h2 class="text-lg font-semibold">Machine</h2>
                <p class="mt-2 text-sm text-muted-foreground">Manage machine groups</p>

                <div class="mt-4">
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto border-collapse">
                            <thead>
                                <tr class="text-left">
                                    <th class="px-3 py-2 text-sm font-medium">ID</th>
                                    <th class="px-3 py-2 text-sm font-medium">Name</th>
                                    <th class="px-3 py-2 text-sm font-medium">Total</th>
                                    <th class="px-3 py-2 text-sm font-medium">Active</th>
                                    <th class="px-3 py-2 text-sm font-medium">Inactive</th>
                                    <th class="px-3 py-2 text-sm font-medium">Created</th>
                                    <th class="px-3 py-2 text-sm font-medium">Actions</th>
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
                                            <IconActionButton :icon="Eye" label="Detail" color="blue" :onClick="() => goDetail(row.machine_group_id)" />
                                            <IconActionButton :icon="Edit2" label="Edit" color="amber" :onClick="() => goEdit(row.machine_group_id)" />
                                            <IconActionButton :icon="Trash2" label="Delete" color="red" :onClick="() => confirmDelete(row.machine_group_id)" />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex items-center justify-end gap-2">
                        <button class="btn" :disabled="!props.machineGroups?.prev_cursor || loading" @click="goPrev">Previous</button>
                        <button class="btn" :disabled="!props.machineGroups?.next_cursor || loading" @click="goNext">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

