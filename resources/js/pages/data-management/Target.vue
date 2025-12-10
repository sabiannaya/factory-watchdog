<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Data Management',
        href: '/data-management/production',
    },
    {
        title: 'Target',
        href: '/data-management/target',
    },
];

const loading = ref(false);

</script>

<template>
    <Head title="Target" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="rounded-xl border border-sidebar-border/70 p-6">
                <h2 class="text-lg font-semibold">Target (placeholder)</h2>
                <p class="mt-2 text-sm text-muted-foreground">This is a dummy page for Target.</p>
            </div>
        </div>
    </AppLayout>
</template>

