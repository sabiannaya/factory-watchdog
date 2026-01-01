<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref, watch, computed } from 'vue';
import { useLocalization } from '@/composables/useLocalization';
import ProductionFormFields from '@/components/production/ProductionFormFields.vue';
import MachineGroupSelector from '@/components/production/MachineGroupSelector.vue';
import type { InputConfig } from '@/composables/useInputConfig';

const { t } = useLocalization();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('data_management.data_management'), href: '/data-management/production' },
    { title: t('data_management.production'), href: '/data-management/production' },
    { title: t('data_management.create'), href: window.location.pathname },
]);

interface MachineGroup {
    machine_group_id: number;
    name: string;
    description?: string;
    input_config?: InputConfig;
}

interface AttachedGroup {
    machine_group_id: number;
    machine_count: number;
    targets: Record<string, number | null>;
    default_targets: Record<string, number | null>;
}

const props = defineProps<{
    machine_groups?: MachineGroup[];
}>();

const form = useForm({
    production_name: '',
    status: 'active',
    target_date: null as string | null,
    machine_groups: {} as Record<number, AttachedGroup>,
});

const targetDate = ref<string | null>(null);
const attachedGroups = ref<Record<number, AttachedGroup>>({});
const clientErrors = ref<Record<number, Record<string, string[]>>>({});

function normalizeTargets(targets?: Record<string, number | null>): Record<string, number | null> {
    const normalized: Record<string, number | null> = {};

    if (targets) {
        Object.entries(targets).forEach(([key, value]) => {
            if (key === 'qty') {
                normalized.qty_normal = value;
                return;
            }

            normalized[key] = value;
        });
    }

    return normalized;
}

// Watch for changes and sync to form
watch([targetDate, attachedGroups], () => {
    form.target_date = targetDate.value;
    form.machine_groups = attachedGroups.value;
}, { deep: true });

function handleAttachedGroupsUpdate(newGroups: Record<number, AttachedGroup>) {
    attachedGroups.value = newGroups;
}

function handleTargetDateUpdate(newDate: string | null) {
    targetDate.value = newDate;
}

function validateClient(): boolean {
    clientErrors.value = {};
    let ok = true;

    Object.values(attachedGroups.value).forEach(group => {
        const errors: Record<string, string[]> = {};

        if (!group.machine_count || group.machine_count < 1) {
            errors.machine_count = ['Machine count must be at least 1'];
            ok = false;
        }

        if (targetDate.value) {
            const normalizedTargets = normalizeTargets(group.targets);
            const hasTargets = Object.values(normalizedTargets).some(v => v !== null && v !== undefined);
            if (!hasTargets) {
                errors.targets = ['At least one target is required when target date is set'];
                ok = false;
            }
        }

        if (Object.keys(errors).length > 0) {
            clientErrors.value[group.machine_group_id] = errors;
        }
    });

    return ok;
}

function submit(): void {
    if (!validateClient()) {
        return;
    }

    // Normalize any legacy qty targets to qty_normal/qty_reject before submit
    const normalizedGroups: Record<number, AttachedGroup> = {};
    Object.entries(attachedGroups.value).forEach(([key, group]) => {
        normalizedGroups[Number(key)] = {
            ...group,
            targets: normalizeTargets(group.targets),
            default_targets: normalizeTargets(group.default_targets),
        };
    });

    form.machine_groups = normalizedGroups;

    form.post('/data-management/production', {
        preserveState: false,
        onSuccess: () => {
            router.get('/data-management/production');
        },
    });
}
</script>

<template>
    <Head :title="t('data_management.create_production')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 space-y-4">
            <div>
                <h1 class="text-3xl font-bold">{{ t('data_management.create_production') }}</h1>
                <p class="text-muted-foreground dark:text-muted-foreground">
                    {{ t('data_management.create_production_description') }}
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <ProductionFormFields :form="form" />

                <MachineGroupSelector
                    :machine-groups="props.machine_groups || []"
                    :attached-groups="attachedGroups"
                    :target-date="targetDate"
                    :errors="clientErrors"
                    @update:attached-groups="handleAttachedGroupsUpdate"
                    @update:target-date="handleTargetDateUpdate"
                />

                <div class="flex items-center gap-3 pt-4">
                        <button type="submit" class="hover:cursor-pointer btn" :disabled="form.processing">
                        {{ form.processing ? t('data_management.creating') : t('data_management.create_production') }}
                    </button>
                    <button
                        type="button"
                        class="btn btn-ghost"
                        @click="router.get('/data-management/production')"
                    >
                        {{ t('data_management.cancel') }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>