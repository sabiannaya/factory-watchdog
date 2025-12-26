<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref, computed, watch } from 'vue';
import useHourlyInputForm from '@/composables/useHourlyInputForm';
import { ChevronDown } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
} from '@/components/ui/dropdown-menu';
import type { InputConfig } from '@/composables/useInputConfig';
import AlertDialog from '@/components/ui/alert-dialog/AlertDialog.vue';
import AlertDialogAction from '@/components/ui/alert-dialog/AlertDialogAction.vue';
import AlertDialogCancel from '@/components/ui/alert-dialog/AlertDialogCancel.vue';
import AlertDialogContent from '@/components/ui/alert-dialog/AlertDialogContent.vue';
import AlertDialogDescription from '@/components/ui/alert-dialog/AlertDialogDescription.vue';
import AlertDialogHeader from '@/components/ui/alert-dialog/AlertDialogHeader.vue';
import AlertDialogTitle from '@/components/ui/alert-dialog/AlertDialogTitle.vue';
import ToastNotifications from '@/components/ToastNotifications.vue';
import { useToast } from '@/composables/useToast';

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
const selectedHour = ref<number>(parseInt(String(props.selectedHour)) || new Date().getHours());
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

const showNormalReject = computed(() => {
    const t = inputConfig.value?.type ?? 'qty_only';
    return t === 'qty_only' || t === 'normal_reject' || fields.value.includes('qty_normal') || fields.value.includes('qty_reject');
});

const showGrades = computed(() => {
    return inputConfig.value?.type === 'grades' || fields.value.includes('grades');
});

const showGradeSelect = computed(() => {
    return inputConfig.value?.type === 'grade_qty' || fields.value.includes('grade');
});

// Form data (use composable to centralize mapping and defaults)
const { form, hasChanged, setInputConfig, resetSnapshot } = useHourlyInputForm(undefined, null);
// Initialize form with preselected values so submitting without changes is correct
form.date = selectedDate.value;
form.hour = Number(selectedHour.value);

const { success, error } = useToast();

// Confirmation dialog state
const showConfirmDialog = ref(false);
const submitting = ref(false);

// Duplicate check state
const duplicateEntry = ref<{ hourly_log_id: number; recorded_at: string } | null>(null);
const checkingDuplicate = ref(false);
let duplicateCheckTimer: ReturnType<typeof setTimeout> | undefined;
const duplicateNotified = ref(false);

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
    // Update input config for grade initialization
    setInputConfig(inputConfig.value);

    // Clear values (user will enter new values for a different machine group)
    form.output_qty_normal = null;
    form.output_qty_reject = null;
    // if setInputConfig initialized grades to zeros, keep them; otherwise ensure object exists
    form.output_grades = form.output_grades ?? {};
    form.output_grade = null;
    form.output_ukuran = null;

    // Check for duplicate with debounce
    if (duplicateCheckTimer) clearTimeout(duplicateCheckTimer);
    duplicateCheckTimer = setTimeout(checkForDuplicate, 300);
});

watch([selectedDate, selectedHour], ([date, hour]) => {
    form.date = date;
    form.hour = Number(hour);

    // Check for duplicate with debounce
    if (duplicateCheckTimer) clearTimeout(duplicateCheckTimer);
    duplicateCheckTimer = setTimeout(checkForDuplicate, 300);
});

// Notify user once when duplicate is detected
watch(duplicateEntry, (val) => {
    if (val && !duplicateNotified.value) {
        error('Existing entry found', `A record already exists for this machine group at ${val.recorded_at}`);
        duplicateNotified.value = true;
    }
    if (!val) duplicateNotified.value = false;
});

const hours = Array.from({ length: 24 }, (_, i) => ({
    value: i,
    label: `${i.toString().padStart(2, '0')}:00`,
}));

const selectedHourLabel = computed(() => hours.find(h => h.value === Number(selectedHour.value))?.label ?? `${String(selectedHour.value).padStart(2,'0')}:00`);
const selectedProductionLabel = computed(() => props.productions.find(p => p.production_id === selectedProduction.value)?.production_name ?? 'Select Production');
const selectedMachineGroupLabel = computed(() => machineGroups.value.find(m => m.production_machine_group_id === selectedMachineGroupId.value)?.name ?? 'Select Machine Group');

const selectHour = (v: number) => { 
    selectedHour.value = Number(v);
    form.hour = Number(v);
};
const selectProduction = (v: number | null) => {
    selectedProduction.value = v;
    // Reset selected machine group, then auto-select the first group if available
    selectedMachineGroupId.value = null;
    if (v !== null) {
        const prod = props.productions.find(p => p.production_id === v);
        if (prod && prod.machine_groups && prod.machine_groups.length > 0) {
            selectedMachineGroupId.value = prod.machine_groups[0].production_machine_group_id;
        }
    }
};
const selectMachineGroup = (v: number | null) => { selectedMachineGroupId.value = v; };

const confirmSubmit = () => {
    submitting.value = true;
    form.post('/input', {
        onSuccess: () => {
            success('Input saved', 'Hourly input has been recorded');
            router.get('/input', { date: form.date, production_id: selectedProduction.value });
        },
        onError: () => {
            error('Failed to save', 'There was an error saving the input');
        },
        onFinish: () => {
            submitting.value = false;
            showConfirmDialog.value = false;
        },
    });
};

const onSubmit = () => {
    showConfirmDialog.value = true;
};

const getFieldLabel = (field: string) => {
    return field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

// Check if there's a duplicate error - use type assertion for custom error key
const hasDuplicateError = computed(() => {
    const errors = form.errors as Record<string, any>;
    return !!errors.duplicate;
});
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
                <p class="text-destructive font-medium">{{ (form.errors as Record<string, string>)['duplicate'] }}</p>
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
                            <DropdownMenu>
                                <DropdownMenuTrigger :as-child="true">
                                    <button type="button" class="input w-full flex items-center justify-between" required>
                                        <span>{{ selectedHourLabel }}</span>
                                        <ChevronDown class="ml-2 size-4" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent class="w-[var(--reka-dropdown-menu-trigger-width)] max-w-none">
                                    <DropdownMenuItem :as-child="true" v-for="h in hours" :key="h.value">
                                        <button class="block w-full text-left px-3 py-2 text-sm" @click="selectHour(h.value)">{{ h.label }}</button>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Production <span class="text-red-500">*</span></label>
                            <DropdownMenu>
                                <DropdownMenuTrigger :as-child="true">
                                    <button type="button" class="input w-full flex items-center justify-between" required>
                                        <span class="truncate">{{ selectedProductionLabel }}</span>
                                        <ChevronDown class="ml-2 size-4" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent class="w-[var(--reka-dropdown-menu-trigger-width)] max-w-none">
                                    <DropdownMenuItem :as-child="true">
                                        <button class="block w-full text-left px-3 py-2 text-sm" @click="selectProduction(null)">Select Production</button>
                                    </DropdownMenuItem>
                                    <template v-for="prod in props.productions" :key="prod.production_id">
                                        <DropdownMenuItem :as-child="true">
                                            <button class="block w-full text-left px-3 py-2 text-sm" @click="selectProduction(prod.production_id)">{{ prod.production_name }}</button>
                                        </DropdownMenuItem>
                                    </template>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Machine Group <span class="text-red-500">*</span></label>
                            <DropdownMenu>
                                <DropdownMenuTrigger :as-child="true">
                                    <button type="button" class="input w-full flex items-center justify-between" :disabled="!selectedProduction">
                                        <span class="truncate">{{ selectedMachineGroupLabel }}</span>
                                        <ChevronDown class="ml-2 size-4" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent class="w-[var(--reka-dropdown-menu-trigger-width)] max-w-none">
                                    <DropdownMenuItem :as-child="true">
                                        <button class="block w-full text-left px-3 py-2 text-sm" @click="selectMachineGroup(null)">Select Machine Group</button>
                                    </DropdownMenuItem>
                                    <template v-for="mg in machineGroups" :key="mg.production_machine_group_id">
                                        <DropdownMenuItem :as-child="true">
                                            <button class="block w-full text-left px-3 py-2 text-sm" @click="selectMachineGroup(mg.production_machine_group_id)">{{ mg.name }} ({{ mg.machine_count }} machines)</button>
                                        </DropdownMenuItem>
                                    </template>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </div>

                <!-- Input Fields Section -->
                <div v-if="machineGroup" class="rounded-lg border border-sidebar-border/70 bg-card p-6">
                    <h2 class="text-lg font-semibold mb-4">Production Output</h2>
                    <p class="text-sm text-muted-foreground mb-4">
                        Input type: <strong>{{ inputConfig?.type ?? 'qty_only' }}</strong>
                    </p>

                    <!-- Normal / Reject quantities (shown for qty_only or normal_reject, or when fields include qty_normal/qty_reject) -->
                    <div v-if="showNormalReject" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Normal Quantity <span class="text-red-500">*</span></label>
                            <input v-model.number="form.output_qty_normal" type="number" min="0" class="input w-full" required />
                            <p v-if="form.errors.output_qty_normal" class="text-red-500 text-sm mt-1">{{ form.errors.output_qty_normal }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Reject Quantity</label>
                            <input v-model.number="form.output_qty_reject" type="number" min="0" class="input w-full" />
                            <p v-if="form.errors.output_qty_reject" class="text-red-500 text-sm mt-1">{{ form.errors.output_qty_reject }}</p>
                        </div>
                    </div>

                    <!-- grades type or fields indicate grades (multiple grade inputs) -->
                    <div v-if="showGrades" class="space-y-4 mt-4">
                        <label class="block text-sm font-medium mb-2">Grades (Multiple)</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <template v-if="inputConfig?.grade_types && inputConfig.grade_types.length">
                                <div v-for="gradeType in inputConfig.grade_types" :key="gradeType">
                                    <label class="block text-sm font-medium mb-2">{{ gradeType }}</label>
                                    <input v-model.number="form.output_grades[gradeType]" type="number" min="0" class="input w-full" />
                                </div>
                            </template>
                            <template v-else>
                                <div v-for="(val, key) in form.output_grades" :key="key">
                                    <label class="block text-sm font-medium mb-2">{{ key }}</label>
                                    <input v-model.number="form.output_grades[key]" type="number" min="0" class="input w-full" />
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- grade_qty type (select a single grade) -->
                    <div v-if="showGradeSelect" class="space-y-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Grade (Single) <span class="text-red-500">*</span></label>
                            <DropdownMenu>
                                <DropdownMenuTrigger :as-child="true">
                                    <button type="button" class="input w-full flex items-center justify-between" required>
                                        <span>{{ form.output_grade ?? 'Select Grade' }}</span>
                                        <ChevronDown class="ml-2 size-4" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent class="w-[var(--radix-dropdown-menu-trigger-width)]">
                                    <DropdownMenuItem :as-child="true">
                                        <button class="block w-full text-left px-3 py-2 text-sm" @click="form.output_grade = null">Select Grade</button>
                                    </DropdownMenuItem>
                                    <template v-if="inputConfig?.grade_types" v-for="g in inputConfig.grade_types" :key="g">
                                        <DropdownMenuItem :as-child="true">
                                            <button class="block w-full text-left px-3 py-2 text-sm" @click="form.output_grade = g">{{ g }}</button>
                                        </DropdownMenuItem>
                                    </template>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>

                    <!-- Additional fields -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="fields.includes('ukuran')">
                            <label class="block text-sm font-medium mb-2">Ukuran (Size)</label>
                            <input v-model="form.output_ukuran" type="text" class="input w-full" placeholder="e.g., 122x244" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Notes (Keterangan)</label>
                            <textarea v-model="form.keterangan" class="input w-full" rows="2" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-3">
                    <button type="submit" class="hover:cursor-pointer btn" :disabled="form.processing || !selectedMachineGroupId">
                        {{ form.processing ? 'Saving...' : 'Save Input' }}
                    </button>
                    <button type="button" class="hover:cursor-pointer btn btn-ghost" @click="router.get('/input')">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <AlertDialog :open="showConfirmDialog" @update:open="showConfirmDialog = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Confirm Save Hourly Input</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to record the hourly input for
                            <strong> {{ selectedProductionLabel }} • {{ selectedMachineGroupLabel }} </strong>
                            on <strong>{{ selectedDate }}</strong> at <strong>{{ selectedHourLabel }}</strong>?
                        </AlertDialogDescription>

                        <div class="mt-4 border-t pt-3">
                            <h3 class="text-sm font-medium mb-2">Preview</h3>
                            <div class="rounded-lg bg-muted/50 p-3">
                                <div class="space-y-1 text-sm">
                                    <div v-if="showNormalReject"><span class="font-medium">Normal:</span> {{ form.output_qty_normal ?? 0 }}</div>
                                    <div v-if="showNormalReject"><span class="font-medium">Reject:</span> {{ form.output_qty_reject ?? 0 }}</div>

                                    <div v-if="showGrades"><span class="font-medium">Grades:</span></div>
                                    <div v-if="showGrades" class="grid grid-cols-2 gap-2 mt-1">
                                        <template v-if="inputConfig?.grade_types && inputConfig.grade_types.length">
                                            <div v-for="g in inputConfig.grade_types" :key="g"><span class="font-medium">{{ g }}:</span> {{ form.output_grades[g] ?? 0 }}</div>
                                        </template>
                                        <template v-else>
                                            <div v-for="(val, key) in form.output_grades" :key="key"><span class="font-medium">{{ key }}:</span> {{ val ?? 0 }}</div>
                                        </template>
                                    </div>

                                    <div v-if="showGradeSelect"><span class="font-medium">Grade:</span> {{ form.output_grade ?? '-' }}</div>
                                    <div v-if="fields.includes('ukuran')"><span class="font-medium">Ukuran:</span> {{ form.output_ukuran ?? '-' }}</div>
                                    <div><span class="font-medium">Notes:</span> {{ form.keterangan ?? '-' }}</div>
                                </div>
                            </div>
                        </div>
                </AlertDialogHeader>
                <div class="flex justify-end gap-2">
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="confirmSubmit" :disabled="submitting" class="btn">
                        {{ submitting ? 'Saving...' : 'Save Input' }}
                    </AlertDialogAction>
                </div>
            </AlertDialogContent>
        </AlertDialog>

        <ToastNotifications />

    </AppLayout>
</template>