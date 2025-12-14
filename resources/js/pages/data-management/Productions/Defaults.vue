<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Data Management',
        href: '/data-management/production',
    },
    {
        title: 'Production Defaults',
        href: '/data-management/productions/defaults',
    },
];

const props = defineProps<{
    productions: Array<{
        production_id: number;
        production_name: string;
        machine_groups: Array<{
            production_machine_group_id: number;
            name: string;
            machine_count: number;
            fields: string[];
            default_targets: Record<string, number | null>;
        }>;
    }>;
}>();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Production Defaults" />

        <div class="p-4 space-y-4">
            <div>
                <h1 class="text-3xl font-bold">Production Defaults Management</h1>
                <p class="text-gray-600">Define default targets for each numeric parameter per machine group. These defaults will be used in the Targets view and can be overridden daily.</p>
            </div>

            <div class="space-y-6">
                <div v-for="production in productions" :key="production.production_id" class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                        <h2 class="text-xl font-bold text-white">{{ production.production_name }}</h2>
                    </div>

                    <div class="p-6">
                        <div v-if="production.machine_groups.length === 0" class="text-center py-8 text-gray-500">
                            <p>No machine groups assigned to this production</p>
                        </div>

                        <div v-else class="space-y-4">
                            <div v-for="mg in production.machine_groups" :key="mg.production_machine_group_id" class="border border-gray-200 rounded-lg p-4 hover:shadow-lg transition">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="font-semibold text-lg">{{ mg.name }}</h3>
                                        <p class="text-sm text-gray-600">Machines: {{ mg.machine_count }}</p>
                                    </div>
                                    <Link
                                        :href="`/data-management/productions/${mg.production_machine_group_id}/defaults/edit`"
                                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm"
                                    >
                                        Edit Defaults
                                    </Link>
                                </div>

                                <div class="bg-gray-50 rounded p-3">
                                    <p class="text-xs font-semibold text-gray-700 mb-2">Input Fields:</p>
                                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                                        <div v-for="field in mg.fields" :key="field" class="bg-white p-2 rounded border border-gray-200">
                                            <p class="text-xs font-medium capitalize mb-1">{{ field }}</p>
                                            <p class="text-sm font-semibold text-blue-600">
                                                {{ mg.default_targets[field] !== null ? mg.default_targets[field] : '—' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
