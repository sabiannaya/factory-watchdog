<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
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
    productionMachineGroup: {
        production_machine_group_id: number;
        name: string;
        machine_count: number;
        default_targets: Record<string, number | null>;
        production: {
            production_id: number;
            production_name: string;
        };
        machineGroup: {
            name: string;
            description: string;
        };
    };
    fields: string[];
    defaultTargets: Record<string, number | null>;
}>();

const form = useForm({
    machine_count: props.productionMachineGroup.machine_count,
    default_targets: props.defaultTargets,
});

const onSubmit = () => {
    form.put(
        `/data-management/productions/${props.productionMachineGroup.production_machine_group_id}/defaults`
    );
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit Production Defaults" />

        <div class="p-4 space-y-4">
            <div>
                <h1 class="text-3xl font-bold">Edit Production Defaults</h1>
                <p class="text-gray-600">{{ productionMachineGroup.production.production_name }} > {{ productionMachineGroup.name }}</p>
            </div>

            <form @submit.prevent="onSubmit" class="bg-white rounded-lg shadow p-6 space-y-6">
                <!-- Machine Count -->
                <div>
                    <label class="block text-sm font-bold mb-2">Number of Machines</label>
                    <input
                        v-model.number="form.machine_count"
                        type="number"
                        min="1"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    />
                    <p class="text-sm text-gray-600 mt-1">
                        This is the number of machines in the {{ productionMachineGroup.name }} group
                    </p>
                </div>

                <!-- Default Targets -->
                <div>
                    <h2 class="text-lg font-bold mb-4">Default Targets</h2>
                    <p class="text-sm text-gray-600 mb-4">Set default target values for each numeric field. Leave empty for no default.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div v-for="field in fields" :key="field" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <label class="block text-sm font-semibold mb-2 capitalize">{{ field }} Default Target</label>
                            <input
                                v-model.number="form.default_targets[field]"
                                type="number"
                                placeholder="Leave empty for no default"
                                min="0"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            />
                            <p class="text-xs text-gray-500 mt-2">This default will be used in daily targets and can be overridden</p>
                        </div>
                    </div>
                </div>

                <!-- Description (Read-only) -->
                <div v-if="productionMachineGroup.machineGroup.description" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm font-medium text-blue-900">Machine Group Description:</p>
                    <p class="text-sm text-blue-800 mt-1">{{ productionMachineGroup.machineGroup.description }}</p>
                </div>

                <!-- Actions -->
                <div class="flex gap-4 pt-4 border-t">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 font-medium"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Defaults' }}
                    </button>
                    <Link
                        :href="`/data-management/productions/defaults`"
                        class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 font-medium"
                    >
                        Cancel
                    </Link>
                </div>

                <!-- Error Messages -->
                <div v-if="Object.keys(form.errors).length > 0" class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <p class="text-sm font-medium text-red-900 mb-2">Errors:</p>
                    <ul class="text-sm text-red-700 space-y-1">
                        <li v-for="(error, field) in form.errors" :key="field">• {{ error }}</li>
                    </ul>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
