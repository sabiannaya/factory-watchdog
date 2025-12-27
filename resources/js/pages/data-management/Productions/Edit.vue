<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref, onMounted } from 'vue';
import ProductionFormFields from '@/components/production/ProductionFormFields.vue';
import MachineGroupSelector from '@/components/production/MachineGroupSelector.vue';
import type { InputConfig } from '@/composables/useInputConfig';

const props = defineProps<{ 
    production: { 
        production_id: number; 
        production_name: string; 
        status: string;
    }, 
    machine_groups?: Array<{
        machine_group_id: number;
        name: string;
        description?: string;
        input_config?: InputConfig;
    }>, 
    attached_groups?: Record<string, {
        production_machine_group_id: number;
        machine_group_id: number;
        machine_count: number;
        default_target: number | null;
        default_targets?: Record<string, number | null>;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Data Management', href: '/data-management/production' },
    { title: 'Production', href: '/data-management/production' },
    { title: props.production?.production_name ?? 'Edit', href: window.location.pathname },
];

interface AttachedGroup {
    machine_group_id: number;
    machine_count: number;
    targets: Record<string, number | null>;
    default_targets: Record<string, number | null>;
}

const form = useForm({
    production_name: props.production.production_name,
    status: props.production.status,
    target_date: null as string | null,
    machine_groups: [] as Array<AttachedGroup>,
});

const targetDate = ref<string | null>(null);
const attachedGroups = ref<Record<number, AttachedGroup>>({});
const clientErrors = ref<Record<number, Record<string, string[]>>>({});

function normalizeTargets(targets?: Record<string, number | null>, legacy?: number | null): Record<string, number | null> {
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

    if (legacy !== undefined && legacy !== null && normalized.qty_normal === undefined) {
        normalized.qty_normal = legacy;
    }

    return normalized;
}

// Initialize attached groups from props
onMounted(() => {
    const groups: Record<number, AttachedGroup> = {};

    if (props.attached_groups) {
        Object.values(props.attached_groups).forEach(ag => {
            groups[ag.machine_group_id] = {
                machine_group_id: ag.machine_group_id,
                machine_count: ag.machine_count,
                targets: normalizeTargets(ag.default_targets, ag.default_target),
                default_targets: normalizeTargets(ag.default_targets, ag.default_target),
            };
        });
    }

    attachedGroups.value = groups;
});

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
            // When target date is set, at least one target should be provided
            const hasTargets = Object.values(group.targets).some(v => v !== null && v !== undefined);
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

    // Populate form fields
    form.target_date = targetDate.value;
    form.machine_groups = Object.values(attachedGroups.value).map(group => ({
        ...group,
        targets: normalizeTargets(group.targets),
        default_targets: normalizeTargets(group.default_targets),
    }));

    form.put(`/data-management/production/${props.production.production_id}`, {
        preserveState: false,
        onSuccess: () => {
            router.get('/data-management/production');
        },
    });
}
</script>

<template>
    <Head :title="`Edit Production — ${props.production.production_name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 space-y-4">
            <div>
                <h1 class="text-3xl font-bold">Edit Production</h1>
                <p class="text-muted-foreground dark:text-muted-foreground">
                    Configure production details and attach machine groups with targets
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
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                    <button
                        type="button"
                        class="btn btn-ghost"
                        @click="router.get('/data-management/production')"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>