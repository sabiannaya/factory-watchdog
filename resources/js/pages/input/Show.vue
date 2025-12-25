<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
        hourlyInput: {
        hourly_log_id: number;
        production_name: string;
        machine_group: string;
        recorded_at: string;
        date: string;
        hour: string;
        output_qty_normal: number | null;
        output_qty_reject: number | null;
        target_qty_normal: number | null;
        target_qty_reject: number | null;
        output_grades: Record<string, number> | null;
        output_grade: string | null;
        output_ukuran: string | null;
        target_grades: Record<string, number> | null;
        target_grade: string | null;
        target_ukuran: string | null;
        keterangan: string | null;
        total_output: number;
        total_target: number;
        created_by: string | null;
        modified_by: string | null;
        created_at: string;
        updated_at: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Input', href: '/input' },
    { title: `${props.hourlyInput.recorded_at}`, href: window.location.pathname },
];

const variance = props.hourlyInput.total_output - props.hourlyInput.total_target;
</script>

<template>
    <Head :title="`Input Detail — ${props.hourlyInput.recorded_at}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 space-y-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Hourly Input Detail</h1>
                    <p class="text-muted-foreground">{{ props.hourlyInput.recorded_at }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button class="btn btn-ghost" @click="router.get(`/input/${props.hourlyInput.hourly_log_id}/edit`)">
                        Edit
                    </button>
                    <button class="btn" @click="router.get('/input')">Back</button>
                </div>
            </div>

            <!-- Summary Card -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-lg border border-sidebar-border/70 bg-card p-4">
                    <p class="text-sm text-muted-foreground">Total Output</p>
                    <p class="text-3xl font-bold">{{ props.hourlyInput.total_output }}</p>
                </div>
                <div class="rounded-lg border border-sidebar-border/70 bg-card p-4">
                    <p class="text-sm text-muted-foreground">Total Target</p>
                    <p class="text-3xl font-bold">{{ props.hourlyInput.total_target }}</p>
                </div>
                <div class="rounded-lg border border-sidebar-border/70 bg-card p-4">
                    <p class="text-sm text-muted-foreground">Variance</p>
                    <p class="text-3xl font-bold" :class="variance >= 0 ? 'text-emerald-600' : 'text-red-600'">
                        {{ variance >= 0 ? '+' : '' }}{{ variance }}
                    </p>
                </div>
            </div>

            <!-- Details -->
            <div class="rounded-lg border border-sidebar-border/70 bg-card p-6">
                <h2 class="text-lg font-semibold mb-4">Details</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                    <div>
                        <p class="text-sm text-muted-foreground">Production</p>
                        <p class="font-medium">{{ props.hourlyInput.production_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Machine Group</p>
                        <p class="font-medium">{{ props.hourlyInput.machine_group }}</p>
                    </div>
                    <!-- Machine index removed: group-level logging only -->
                    <div>
                        <p class="text-sm text-muted-foreground">Date & Hour</p>
                        <p class="font-medium">{{ props.hourlyInput.date }} at {{ props.hourlyInput.hour }}:00</p>
                    </div>
                </div>
            </div>

            <!-- Input Values -->
            <div class="rounded-lg border border-sidebar-border/70 bg-card p-6">
                <h2 class="text-lg font-semibold mb-4">Output & Target Values</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Output Section -->
                    <div>
                        <h3 class="text-sm font-medium text-muted-foreground mb-3">Output</h3>
                        <div class="space-y-3">
                            <div v-if="props.hourlyInput.output_qty_normal !== null">
                                <p class="text-sm text-muted-foreground">Normal Quantity</p>
                                <p class="text-lg font-semibold">{{ props.hourlyInput.output_qty_normal }}</p>
                            </div>
                            <div v-if="props.hourlyInput.output_qty_reject !== null">
                                <p class="text-sm text-muted-foreground">Reject Quantity</p>
                                <p class="text-lg font-semibold">{{ props.hourlyInput.output_qty_reject }}</p>
                            </div>
                            <div v-if="props.hourlyInput.output_grade">
                                <p class="text-sm text-muted-foreground">Grade</p>
                                <p class="text-lg font-semibold">{{ props.hourlyInput.output_grade }}</p>
                            </div>
                            <div v-if="props.hourlyInput.output_ukuran">
                                <p class="text-sm text-muted-foreground">Ukuran (Size)</p>
                                <p class="text-lg font-semibold">{{ props.hourlyInput.output_ukuran }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Target Section -->
                    <div>
                        <h3 class="text-sm font-medium text-muted-foreground mb-3">Target</h3>
                        <div class="space-y-3">
                            <div v-if="props.hourlyInput.target_qty_normal !== null">
                                <p class="text-sm text-muted-foreground">Normal Quantity</p>
                                <p class="text-lg font-semibold">{{ props.hourlyInput.target_qty_normal }}</p>
                            </div>
                            <div v-if="props.hourlyInput.target_qty_reject !== null">
                                <p class="text-sm text-muted-foreground">Reject Quantity</p>
                                <p class="text-lg font-semibold">{{ props.hourlyInput.target_qty_reject }}</p>
                            </div>
                            <div v-if="props.hourlyInput.target_grade">
                                <p class="text-sm text-muted-foreground">Grade</p>
                                <p class="text-lg font-semibold">{{ props.hourlyInput.target_grade }}</p>
                            </div>
                            <div v-if="props.hourlyInput.target_ukuran">
                                <p class="text-sm text-muted-foreground">Ukuran (Size)</p>
                                <p class="text-lg font-semibold">{{ props.hourlyInput.target_ukuran }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grades breakdown -->
                <div v-if="props.hourlyInput.output_grades && Object.keys(props.hourlyInput.output_grades).length > 0" class="mt-6">
                    <h3 class="text-sm font-medium text-muted-foreground mb-3">Output Grades Breakdown</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <div v-for="(value, key) in props.hourlyInput.output_grades" :key="key" class="rounded bg-muted/40 p-3">
                            <p class="text-xs text-muted-foreground">{{ key }}</p>
                            <p class="text-lg font-semibold">{{ value }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="props.hourlyInput.target_grades && Object.keys(props.hourlyInput.target_grades).length > 0" class="mt-4">
                    <h3 class="text-sm font-medium text-muted-foreground mb-3">Target Grades Breakdown</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <div v-for="(value, key) in props.hourlyInput.target_grades" :key="key" class="rounded bg-muted/40 p-3">
                            <p class="text-xs text-muted-foreground">{{ key }}</p>
                            <p class="text-lg font-semibold">{{ value }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="props.hourlyInput.keterangan" class="mt-6 pt-6 border-t">
                    <p class="text-sm text-muted-foreground">Notes</p>
                    <p class="font-medium mt-1">{{ props.hourlyInput.keterangan }}</p>
                </div>
            </div>

            <!-- Audit Info -->
            <div class="rounded-lg border border-sidebar-border/70 bg-card p-6">
                <h2 class="text-lg font-semibold mb-4">Audit Information</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                    <div>
                        <p class="text-sm text-muted-foreground">Created By</p>
                        <p class="font-medium">{{ props.hourlyInput.created_by ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Created At</p>
                        <p class="font-medium">{{ props.hourlyInput.created_at }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Modified By</p>
                        <p class="font-medium">{{ props.hourlyInput.modified_by ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Updated At</p>
                        <p class="font-medium">{{ props.hourlyInput.updated_at }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
