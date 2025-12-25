<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref, computed, watch, reactive } from 'vue';
import type { InputConfig } from '@/composables/useInputConfig';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Input', href: '/input' },
    { title: 'Record Input', href: window.location.pathname },
];

interface MachineGroup {
    production_machine_group_id: number;
    name: string;
    machine_count: number;
    default_targets: Record<string, number | null>;
    fields: string[];
    input_config?: InputConfig;
}

interface Production {
    production_id: number;
    production_name: string;
    machine_groups: MachineGroup[];
}

const props = defineProps<{
    productions: Production[];
    selectedProductionId: number | null;
    selectedDate: string;
    selectedHour: string;
}>();

const selectedProduction = ref<number | null>(props.selectedProductionId);
const selectedDate = ref(props.selectedDate);
const selectedHour = ref(parseInt(props.selectedHour) || new Date().getHours());
const selectedMachineGroupId = ref<number | null>(null);

const production = computed(() => 
    props.productions.find(p => p.production_id === selectedProduction.value)
);

const machineGroups = computed(() => production.value?.machine_groups ?? []);

const machineGroup = computed(() => 
    machineGroups.value.find(mg => mg.production_machine_group_id === selectedMachineGroupId.value)
);

const inputConfig = computed(() => machineGroup.value?.input_config);
const fields = computed(() => machineGroup.value?.fields ?? []);

// Form data
const form = useForm({
    production_machine_group_id: null as number | null,
    date: props.selectedDate,
    hour: parseInt(props.selectedHour) || new Date().getHours(),
    qty: null as number | null,
    qty_normal: null as number | null,
    qty_reject: null as number | null,
    grades: {} as Record<string, number | null>,
    grade: null as string | null,
    ukuran: null as string | null,
    keterangan: null as string | null,
});

// Duplicate check state
const duplicateEntry = ref<{ hourly_log_id: number; recorded_at: string } | null>(null);
const checkingDuplicate = ref(false);
let duplicateCheckTimer: ReturnType<typeof setTimeout> | undefined;

// Check for duplicate when relevant fields change
const checkForDuplicate = async () => {
    if (!selectedMachineGroupId.value || !selectedDate.value) {
        duplicateEntry.value = null;
        return;
    }

    checkingDuplicate.value = true;
    try {
        const response = await fetch('/input/check-duplicate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                production_machine_group_id: selectedMachineGroupId.value,
                date: selectedDate.value,
                hour: selectedHour.value,
            }),
        });
        const data = await response.json();
        duplicateEntry.value = data.exists ? data.entry : null;
    } catch (error) {
        console.error('Error checking duplicate:', error);
    } finally {
        checkingDuplicate.value = false;
    }
};

// Update form when machine group changes
watch(selectedMachineGroupId, (newId) => {
    form.production_machine_group_id = newId;
    // Reset input fields
    form.qty = null;
    form.qty_normal = null;
    form.qty_reject = null;
    form.grades = {};
    form.grade = null;
    form.ukuran = null;
    
    // Initialize grades if needed
    if (inputConfig.value?.type === 'grades' && inputConfig.value.grade_types) {
        inputConfig.value.grade_types.forEach(g => {
            form.grades[g] = null;
        });
    }

    // Check for duplicate with debounce
    if (duplicateCheckTimer) clearTimeout(duplicateCheckTimer);
    duplicateCheckTimer = setTimeout(checkForDuplicate, 300);
});

watch([selectedDate, selectedHour], ([date, hour]) => {
    form.date = date;
    form.hour = hour;

    // Check for duplicate with debounce
    if (duplicateCheckTimer) clearTimeout(duplicateCheckTimer);
    duplicateCheckTimer = setTimeout(checkForDuplicate, 300);
});

const hours = Array.from({ length: 24 }, (_, i) => ({
    value: i,
    label: `${i.toString().padStart(2, '0')}:00`,
}));

const onSubmit = () => {
    if (duplicateEntry.value) {
        if (!window.confirm('A record already exists for this selection. Are you sure you want to create another entry?')) {
            return;
        }
    }
    form.post('/input', {
        onSuccess: () => {
            router.get('/input', { date: form.date, production_id: selectedProduction.value });
        },
    });
};

const getFieldLabel = (field: string) => {
    return field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

// Check if there's a duplicate error
const hasDuplicateError = computed(() => !!form.errors.duplicate);
</script>

<template>
    <Head title="Record Hourly Input" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 space-y-6">
            <div>
                <h1 class="text-2xl font-bold">Record Hourly Input</h1>
                <p class="text-muted-foreground">Record production output for a specific hour</p>
            </div>

            <!-- Duplicate Error Alert (from server) -->
            <div v-if="hasDuplicateError" class="rounded-lg bg-destructive/10 border border-destructive/30 p-4">
                <p class="text-destructive font-medium">{{ form.errors.duplicate }}</p>
            </div>

            <!-- Duplicate Warning (real-time check) -->
            <div v-if="duplicateEntry && !hasDuplicateError" class="rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-300 dark:border-amber-700 p-4">
                <div class="flex items-start gap-3">
                    <svg class="size-5 text-amber-600 dark:text-amber-400 mt-0.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <p class="font-medium text-amber-800 dark:text-amber-200">Existing Entry Found</p>
                        <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                            A record already exists for this machine group at {{ duplicateEntry.recorded_at }}.
                            <Link :href="`/input/${duplicateEntry.hourly_log_id}/edit`" class="underline font-medium">
                                Edit existing entry
                            </Link>
                            instead?
                        </p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="onSubmit" class="space-y-6">
                <!-- Selection Section -->
                <div class="rounded-lg border border-sidebar-border/70 bg-card p-6">
                    <h2 class="text-lg font-semibold mb-4">Select Production & Time</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Date <span class="text-red-500">*</span></label>
                            <input v-model="selectedDate" type="date" class="input w-full" required />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2">Hour <span class="text-red-500">*</span></label>
                            <select v-model.number="selectedHour" class="input w-full" required>
                                <option v-for="h in hours" :key="h.value" :value="h.value">{{ h.label }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Production <span class="text-red-500">*</span></label>
                            <select v-model.number="selectedProduction" class="input w-full" required>
                                <option :value="null">-- Select Production --</option>
                                <option v-for="prod in props.productions" :key="prod.production_id" :value="prod.production_id">
                                    {{ prod.production_name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Machine Group <span class="text-red-500">*</span></label>
                            <select v-model.number="selectedMachineGroupId" class="input w-full" required :disabled="!selectedProduction">
                                <option :value="null">-- Select Machine Group --</option>
                                <option v-for="mg in machineGroups" :key="mg.production_machine_group_id" :value="mg.production_machine_group_id">
                                    {{ mg.name }} ({{ mg.machine_count }} machines)
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Input Fields Section -->
                <div v-if="machineGroup" class="rounded-lg border border-sidebar-border/70 bg-card p-6">
                    <h2 class="text-lg font-semibold mb-4">Production Output</h2>
                    <p class="text-sm text-muted-foreground mb-4">
                        Input type: <strong>{{ inputConfig?.type ?? 'qty_only' }}</strong>
                    </p>

                    <!-- qty_only type -->
                    <div v-if="!inputConfig || inputConfig.type === 'qty_only'" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Quantity <span class="text-red-500">*</span></label>
                            <input v-model.number="form.qty" type="number" min="0" class="input w-full max-w-xs" required />
                            <p v-if="form.errors.qty" class="text-red-500 text-sm mt-1">{{ form.errors.qty }}</p>
                        </div>
                    </div>

                    <!-- normal_reject type -->
                    <div v-else-if="inputConfig.type === 'normal_reject'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Normal Quantity <span class="text-red-500">*</span></label>
                            <input v-model.number="form.qty_normal" type="number" min="0" class="input w-full" required />
                            <p v-if="form.errors.qty_normal" class="text-red-500 text-sm mt-1">{{ form.errors.qty_normal }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Reject Quantity</label>
                            <input v-model.number="form.qty_reject" type="number" min="0" class="input w-full" />
                            <p v-if="form.errors.qty_reject" class="text-red-500 text-sm mt-1">{{ form.errors.qty_reject }}</p>
                        </div>
                    </div>

                    <!-- grades type -->
                    <div v-else-if="inputConfig.type === 'grades'" class="space-y-4">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <div v-for="gradeType in inputConfig.grade_types" :key="gradeType">
                                <label class="block text-sm font-medium mb-2">{{ gradeType }}</label>
                                <input v-model.number="form.grades[gradeType]" type="number" min="0" class="input w-full" />
                            </div>
                        </div>
                    </div>

                    <!-- grade_qty type (Film) -->
                    <div v-else-if="inputConfig.type === 'grade_qty'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Grade <span class="text-red-500">*</span></label>
                            <select v-model="form.grade" class="input w-full" required>
                                <option :value="null">-- Select Grade --</option>
                                <option v-for="g in inputConfig.grade_types" :key="g" :value="g">{{ g }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Quantity <span class="text-red-500">*</span></label>
                            <input v-model.number="form.qty" type="number" min="0" class="input w-full" required />
                        </div>
                    </div>

                    <!-- Additional fields -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="fields.includes('ukuran')">
                            <label class="block text-sm font-medium mb-2">Ukuran (Size)</label>
                            <input v-model="form.ukuran" type="text" class="input w-full" placeholder="e.g., 122x244" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Notes (Keterangan)</label>
                            <textarea v-model="form.keterangan" class="input w-full" rows="2" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-3">
                    <button type="submit" class="btn" :disabled="form.processing || !selectedMachineGroupId">
                        {{ form.processing ? 'Saving...' : 'Save Input' }}
                    </button>
                    <button type="button" class="btn btn-ghost" @click="router.get('/input')">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
