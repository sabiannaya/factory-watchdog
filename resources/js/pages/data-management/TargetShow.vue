<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{ dailyTarget: { daily_target_id: number; date: string; target_value: number; actual_value: number; notes?: string } }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Data Management', href: '/data-management/production' },
    { title: 'Target', href: '/data-management/target' },
    { title: props.dailyTarget?.date ?? 'Detail', href: window.location.pathname },
];

const goBack = () => router.get('/data-management/target');
</script>

<template>
    <Head :title="`Target — ${props.dailyTarget?.date ?? ''}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="rounded-xl border border-sidebar-border/70 p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-semibold">{{ props.dailyTarget.date }}</h2>
                        <p class="mt-1 text-sm text-muted-foreground">Target: <strong>{{ props.dailyTarget.target_value }}</strong></p>
                        <p class="mt-1 text-sm text-muted-foreground">Actual: <strong>{{ props.dailyTarget.actual_value }}</strong></p>
                        <p class="mt-2 text-sm">Notes: {{ props.dailyTarget.notes ?? '—' }}</p>
                    </div>
                    <div>
                        <button class="btn" @click="goBack">Back</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
