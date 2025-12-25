<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { computed } from 'vue';
import type { InputConfig } from '@/composables/useInputConfig';

const props = defineProps<{
    hourlyInput: {
        hourly_log_id: number;
        production_machine_group_id: number;
        production_name: string;
        machine_group: string;
        machine_index: number;
        date: string;
        hour: number;
        output_value: number;
        target_value: number;
        qty: number | null;
        qty_normal: number | null;
        qty_reject: number | null;
        grades: Record<string, number> | null;
        grade: string | null;
        ukuran: string | null;
        keterangan: string | null;
    };
    inputConfig: InputConfig | null;
    fields: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Input', href: '/input' },
    { title: 'Edit', href: window.location.pathname },
];

const form = useForm({
    date: props.hourlyInput.date,
    hour: props.hourlyInput.hour,
    qty: props.hourlyInput.qty,
    qty_normal: props.hourlyInput.qty_normal,
    qty_reject: props.hourlyInput.qty_reject,
    grades: props.hourlyInput.grades ?? {},
    grade: props.hourlyInput.grade,
    ukuran: props.hourlyInput.ukuran,
    keterangan: props.hourlyInput.keterangan,
});

const hours = Array.from({ length: 24 }, (_, i) => ({
    value: i,
    label: `${i.toString().padStart(2, '0')}:00`,
}));

const inputType = computed(() => props.inputConfig?.type ?? 'qty_only');

const onSubmit = () => {
    form.put(`/input/${props.hourlyInput.hourly_log_id}`, {
        onSuccess: () => {
            router.get('/input', { date: form.date });
        },
    });
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
                            <select v-model.number="form.hour" class="input w-full" required>
                                <option v-for="h in hours" :key="h.value" :value="h.value">{{ h.label }}</option>
                            </select>
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

                    <!-- qty_only type -->
                    <div v-if="inputType === 'qty_only'" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Quantity <span class="text-red-500">*</span></label>
                            <input v-model.number="form.qty" type="number" min="0" class="input w-full max-w-xs" required />
                            <p v-if="form.errors.qty" class="text-red-500 text-sm mt-1">{{ form.errors.qty }}</p>
                        </div>
                    </div>

                    <!-- normal_reject type -->
                    <div v-else-if="inputType === 'normal_reject'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                    <div v-else-if="inputType === 'grades' && props.inputConfig?.grade_types" class="space-y-4">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <div v-for="gradeType in props.inputConfig.grade_types" :key="gradeType">
                                <label class="block text-sm font-medium mb-2">{{ gradeType }}</label>
                                <input v-model.number="form.grades[gradeType]" type="number" min="0" class="input w-full" />
                            </div>
                        </div>
                    </div>

                    <!-- grade_qty type (Film) -->
                    <div v-else-if="inputType === 'grade_qty'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Grade <span class="text-red-500">*</span></label>
                            <select v-model="form.grade" class="input w-full" required>
                                <option :value="null">-- Select Grade --</option>
                                <option v-for="g in props.inputConfig?.grade_types" :key="g" :value="g">{{ g }}</option>
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
                    <button type="submit" class="btn" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Update Input' }}
                    </button>
                    <button type="button" class="btn btn-ghost" @click="router.get('/input')">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
