<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';
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
import AlertDialogFooter from '@/components/ui/alert-dialog/AlertDialogFooter.vue';
import ToastNotifications from '@/components/ToastNotifications.vue';
import { useToast } from '@/composables/useToast';

const props = defineProps<{
    hourlyInput: {
        hourly_log_id: number;
        production_machine_group_id: number;
        production_name: string;
        machine_group: string;
        date: string;
        hour: number;
        output_value?: number;
        target_value?: number;
        // legacy keys
        qty?: number | null;
        qty_normal?: number | null;
        qty_reject?: number | null;
        grades?: Record<string, number> | null;
        grade?: string | null;
        ukuran?: string | null;
        // controller keys
        output_qty_normal?: number | null;
        output_qty_reject?: number | null;
        output_grades?: Record<string, number> | null;
        output_grade?: string | null;
        output_ukuran?: string | null;
        keterangan?: string | null;
    };
    inputConfig: InputConfig | null;
    fields: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Input', href: '/input' },
    { title: 'Edit', href: window.location.pathname },
];

const { form, hasChanged, resetSnapshot } = useHourlyInputForm(props.hourlyInput, props.inputConfig);

const hours = Array.from({ length: 24 }, (_, i) => ({
    value: i,
    label: `${i.toString().padStart(2, '0')}:00`,
}));

const hourLabel = computed(() => {
    const current = Number(form.hour);
    return hours.find(h => h.value === current)?.label ?? `${String(current).padStart(2,'0')}:00`;
});
const selectHour = (v: number) => { form.hour = v; };

const inputType = computed(() => props.inputConfig?.type ?? 'qty_only');


const showNormalReject = computed(() => {
    const t = inputType.value;
    return t === 'qty_only' || t === 'normal_reject' || props.fields.includes('qty_normal') || props.fields.includes('qty_reject');
});

const showGrades = computed(() => {
    return inputType.value === 'grades' || props.fields.includes('grades');
});

const showGradeSelect = computed(() => {
    return inputType.value === 'grade_qty' || props.fields.includes('grade');
});

const { success, error } = useToast();
const showConfirmDialog = ref(false);
const submitting = ref(false);

const confirmSubmit = () => {
    submitting.value = true;
    form.put(`/input/${props.hourlyInput.hourly_log_id}`, {
        onSuccess: () => {
            success('Input updated', 'Hourly input has been updated');
            router.get('/input', { date: form.date });
        },
        onError: () => {
            error('Failed to update', 'There was an error updating the input');
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
</script>

<template>
    <Head title="Edit Hourly Input" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 space-y-6">
            <div>
                <h1 class="text-2xl font-bold">Edit Hourly Input</h1>
                <p class="text-muted-foreground">
                    {{ props.hourlyInput.production_name }} • {{ props.hourlyInput.machine_group }}
                </p>
            </div>

            <form @submit.prevent="onSubmit" class="space-y-6">
                <!-- Time Section -->
                <div class="rounded-lg border border-sidebar-border/70 bg-card p-6">
                    <h2 class="text-lg font-semibold mb-4">Date & Time</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Date <span class="text-red-500">*</span></label>
                            <input v-model="form.date" type="date" class="input w-full" required />
                            <p v-if="form.errors.date" class="text-red-500 text-sm mt-1">{{ form.errors.date }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2">Hour <span class="text-red-500">*</span></label>
                            <DropdownMenu>
                                <DropdownMenuTrigger :as-child="true">
                                    <button type="button" class="input w-full flex items-center justify-between" required>
                                        <span>{{ hourLabel }}</span>
                                        <ChevronDown class="ml-2 size-4" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent class="w-[var(--radix-dropdown-menu-trigger-width)]">
                                    <DropdownMenuItem :as-child="true" v-for="h in hours" :key="h.value">
                                        <button class="block w-full text-left px-3 py-2 text-sm" @click="selectHour(h.value)">{{ h.label }}</button>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                            <p v-if="form.errors.hour" class="text-red-500 text-sm mt-1">{{ form.errors.hour }}</p>
                        </div>
                    </div>
                </div>

                <!-- Input Fields Section -->
                <div class="rounded-lg border border-sidebar-border/70 bg-card p-6">
                    <h2 class="text-lg font-semibold mb-4">Production Output</h2>
                    <p class="text-sm text-muted-foreground mb-4">
                        Input type: <strong>{{ inputType }}</strong>
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
                            <template v-if="props.inputConfig?.grade_types && props.inputConfig.grade_types.length">
                                <div v-for="gradeType in props.inputConfig.grade_types" :key="gradeType">
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
                                    <template v-if="props.inputConfig?.grade_types" v-for="g in props.inputConfig.grade_types" :key="g">
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
                    <button type="submit" class="hover:cursor-pointer btn" :disabled="form.processing || !hasChanged">
                        {{ form.processing ? 'Saving...' : 'Update Input' }}
                    </button>
                    <button type="button" class="hover:cursor-pointer btn btn-ghost" @click="router.get('/input')">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
    <!-- Confirmation dialog for updating -->
    <AlertDialog :open="showConfirmDialog" @update:open="(v) => showConfirmDialog = v">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Update Hourly Input?</AlertDialogTitle>
                <AlertDialogDescription>
                    This will update the hourly input record. Changes are permanent.
                </AlertDialogDescription>

                <div class="mt-4 border-t pt-3">
                    <h3 class="text-sm font-medium mb-2">Preview</h3>
                    <div class="rounded-lg bg-muted/50 p-3">
                        <div class="space-y-1 text-sm">
                            <div v-if="showNormalReject"><span class="font-medium">Normal:</span> {{ form.output_qty_normal ?? 0 }}</div>
                            <div v-if="showNormalReject"><span class="font-medium">Reject:</span> {{ form.output_qty_reject ?? 0 }}</div>

                            <div v-if="showGrades"><span class="font-medium">Grades:</span></div>
                            <div v-if="showGrades" class="grid grid-cols-2 gap-2 mt-1">
                                <template v-if="props.inputConfig?.grade_types && props.inputConfig.grade_types.length">
                                    <div v-for="g in props.inputConfig.grade_types" :key="g"><span class="font-medium">{{ g }}:</span> {{ form.output_grades[g] ?? 0 }}</div>
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
                <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction class="bg-blue-600 hover:bg-blue-700 text-white" @click="confirmSubmit">Update</AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
