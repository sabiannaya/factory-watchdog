<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import IconActionButton from '@/components/ui/IconActionButton.vue';
import { Edit2, Trash2, ArrowLeft } from 'lucide-vue-next';
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
        output_grades: Record<string, number> | null;
        output_grade: string | null;
        output_ukuran: string | null;
        keterangan: string | null;
        total_output: number;
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

const goEdit = () => {
    router.get(`/input/${props.hourlyInput.hourly_log_id}/edit`);
};

const goBack = () => router.get('/input');

const confirmDelete = () => {
    // simple confirm for consistency with other list pages
    if (!window.confirm('Are you sure you want to delete this hourly input?')) return;
    router.visit(`/input/${props.hourlyInput.hourly_log_id}`, { method: 'delete', preserveState: false });
};
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
                    <IconActionButton :icon="ArrowLeft" label="Back" color="blue" :onClick="goBack" />
                    <IconActionButton :icon="Edit2" label="Edit" color="amber" :onClick="goEdit" />
                    <IconActionButton :icon="Trash2" label="Delete" color="red" :onClick="confirmDelete" />
                </div>
            </div>

            <!-- Summary Card -->
            <div class="rounded-lg border border-sidebar-border/70 bg-card p-6">
                <p class="text-sm text-muted-foreground">Total Output</p>
                <p class="text-3xl font-bold">{{ props.hourlyInput.total_output }}</p>
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
                <h2 class="text-lg font-semibold mb-4">Output Values</h2>

                <div class="space-y-3">
                    <!-- Output Section -->
                    <div>
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

                <!-- Grades breakdown -->
                <div v-if="props.hourlyInput.output_grades && Object.keys(props.hourlyInput.output_grades).length > 0"
                    class="mt-6">
                    <h3 class="text-sm font-medium text-muted-foreground mb-3">Output Grades Breakdown</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <div v-for="(value, key) in props.hourlyInput.output_grades" :key="key"
                            class="rounded bg-muted/40 p-3">
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
