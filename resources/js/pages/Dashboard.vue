<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import DashboardCard from '../components/DashboardCard.vue';
import { ref } from 'vue';


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const loading = ref(false);

</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <DashboardCard
                        title="Current Output"
                        :value="100"
                        :delta="20"
                        deltaDirection="up"
                        :small="80"
                        subtitle="Wed, 30 Dec 2025"
                        :loading="false"
                    />
                </div>
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <DashboardCard
                        title="Downtime (mins)"
                        :value="5"
                        :delta="1"
                        deltaDirection="down"
                        :small="0"
                        subtitle="Today"
                        :loading="false"
                    />
                </div>
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <DashboardCard
                        title="Efficiency"
                        value="78%"
                        :delta="3"
                        deltaDirection="up"
                        small="target 85%"
                        subtitle="Today"
                        :loading="false"
                    />
                </div>
            </div>
            <div
                class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
            >
                <DashboardCard
                    title="Total Production"
                    :value="1240"
                    :delta="12"
                    deltaDirection="up"
                    small="expected 1200"
                    subtitle="Wed, 30 Dec 2025"
                    :loading="false"
                />
            </div>
        </div>
    </AppLayout>
</template>
