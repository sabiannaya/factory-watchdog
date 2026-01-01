<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { useLocalization } from '@/composables/useLocalization';

const { t } = useLocalization();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: t('data_management.data_management'),
        href: '/data-management/production',
    },
    {
        title: t('data_management.targets'),
        href: '/data-management/targets',
    },
    {
        title: t('data_management.edit'),
        href: window.location.pathname,
    },
]);

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
    values: Array<{
        field_name: string;
        target_value: number | null;
        keterangan: string | null;
    }>;
    date: string;
}>();

const form = useForm({
    date: props.date,
    values: props.values,
});

// Collapsible group panel (single group edit page)
const open = ref(true);

const onSubmit = () => {
    form.put(`/data-management/targets/${props.productionMachineGroup.production_machine_group_id}`, {
        onSuccess: () => {
            router.get(`/data-management/targets?production_id=${props.productionMachineGroup.production.production_id}&date=${props.date}`);
        },
    });
};

function getFieldLabel(fieldName: string): string {
    return fieldName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('data_management.edit_daily_targets')" />

        <div class="p-4 space-y-4">
            <div>
                <h1 class="text-3xl font-bold">{{ t('data_management.edit_daily_targets') }}</h1>
                <p class="text-muted-foreground dark:text-muted-foreground">
                    {{ productionMachineGroup.production.production_name }} • {{ productionMachineGroup.name }} • {{ date }}
                </p>
            </div>

            <form @submit.prevent="onSubmit" class="space-y-6">
                <div class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border/70 bg-card dark:bg-card">
                    <div class="p-4 border-b border-sidebar-border/40 flex items-start justify-between">
                        <div>
                            <h2 class="text-lg font-semibold mb-1">{{ productionMachineGroup.machineGroup.name }}</h2>
                            <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                                {{ productionMachineGroup.machineGroup.description }}
                            </p>
                            <p class="text-sm text-muted-foreground dark:text-muted-foreground mt-2">
                                {{ productionMachineGroup.machine_count }} {{ t('data_management.machines') }}
                            </p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-ghost" @click="open = !open">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" :class="{'-rotate-90': !open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div v-show="open" class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="(value, index) in form.values" :key="index" class="rounded-lg bg-muted/40 dark:bg-muted/40 p-4 space-y-3">
                                <h4 class="font-semibold text-base">{{ getFieldLabel(value.field_name) }}</h4>

                                <div>
                                    <label class="block text-sm font-medium mb-1.5">{{ t('data_management.target_value') }}</label>
                                    <input
                                        v-model.number="value.target_value"
                                        type="number"
                                        :placeholder="t('data_management.enter_target')"
                                        min="0"
                                        class="input w-full"
                                    />
                                    <p class="text-xs text-muted-foreground dark:text-muted-foreground mt-1">
                                        {{ t('data_management.default') }}: {{ productionMachineGroup.default_targets[value.field_name] ?? t('data_management.none') }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-1.5">{{ t('data_management.notes') }}</label>
                                    <textarea
                                        v-model="value.keterangan"
                                        :placeholder="t('data_management.optional_notes')"
                                        rows="2"
                                        class="input w-full resize-none"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="hover:cursor-pointer btn"
                    >
                        {{ form.processing ? t('data_management.saving') : t('data_management.save_changes') }}
                    </button>
                    <Link
                        :href="`/data-management/targets?production_id=${productionMachineGroup.production.production_id}&date=${date}`"
                        class="btn btn-ghost"
                    >
                        {{ t('data_management.cancel') }}
                    </Link>
                </div>

                <div v-if="Object.keys(form.errors).length > 0" class="rounded-lg bg-destructive/10 dark:bg-destructive/10 border border-destructive/20 dark:border-destructive/20 p-4">
                    <p class="text-sm font-medium text-destructive dark:text-destructive mb-2">{{ t('data_management.errors') }}:</p>
                    <ul class="text-sm text-destructive dark:text-destructive space-y-1">
                        <li v-for="(error, field) in form.errors" :key="field">• {{ error }}</li>
                    </ul>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
