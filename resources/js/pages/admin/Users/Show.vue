<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import IconActionButton from '@/components/ui/IconActionButton.vue';
import { type BreadcrumbItem } from '@/types';
import { User, Shield, Factory, Calendar, Mail, ArrowLeft, Edit2, Trash2 } from 'lucide-vue-next';

interface Production {
    production_id: number;
    production_name: string;
}

interface UserData {
    id: number;
    name: string;
    email: string;
    role_id: number | null;
    role_name: string;
    role_slug: string | null;
    created_at: string;
    productions: Production[];
}

const props = defineProps<{
    user: UserData;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/users' },
    { title: 'Users', href: '/admin/users' },
    { title: props.user.name, href: `/admin/users/${props.user.id}` },
];

const getRoleBadgeClass = (slug: string | null) => {
    if (slug === 'super') return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
    if (slug === 'staff') return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

const confirmDelete = () => {
    if (!window.confirm('Are you sure you want to delete this user? This action cannot be undone.')) return;
    router.delete(`/admin/users/${props.user.id}`);
};
</script>

<template>
    <Head :title="`User - ${user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <button
                        class="text-sm text-muted-foreground hover:text-foreground flex items-center gap-1 mb-2 transition-colors"
                        @click="router.get('/admin/users')"
                    >
                        <ArrowLeft class="size-4" />
                        Back to Users
                    </button>
                    <h2 class="text-2xl font-semibold flex items-center gap-2">
                        <User class="size-6" />
                        {{ user.name }}
                    </h2>
                </div>
                <div class="flex items-center gap-2">
                    <IconActionButton :icon="Edit2" label="Edit" color="amber" :onClick="() => router.get(`/admin/users/${user.id}/edit`)" />
                    <IconActionButton :icon="Trash2" label="Delete" color="red" :onClick="confirmDelete" />
                </div>
            </div>

            <!-- User Info Card -->
            <div class="rounded-lg border overflow-hidden">
                <div class="bg-muted/30 px-6 py-4 border-b">
                    <h3 class="font-medium">User Information</h3>
                </div>
                
                <div class="p-6 space-y-4">
                    <!-- Email -->
                    <div class="flex items-start gap-4">
                        <div class="p-2 rounded-md bg-muted">
                            <Mail class="size-5 text-muted-foreground" />
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Email</p>
                            <p class="font-medium">{{ user.email }}</p>
                        </div>
                    </div>

                    <!-- Role -->
                    <div class="flex items-start gap-4">
                        <div class="p-2 rounded-md bg-muted">
                            <Shield class="size-5 text-muted-foreground" />
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Role</p>
                            <span 
                                class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium mt-1"
                                :class="getRoleBadgeClass(user.role_slug)"
                            >
                                <Shield v-if="user.role_slug === 'super'" class="size-3" />
                                {{ user.role_name }}
                            </span>
                        </div>
                    </div>

                    <!-- Created -->
                    <div class="flex items-start gap-4">
                        <div class="p-2 rounded-md bg-muted">
                            <Calendar class="size-5 text-muted-foreground" />
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Created</p>
                            <p class="font-medium">{{ user.created_at }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Production Assignments -->
            <div class="rounded-lg border overflow-hidden mt-6">
                <div class="bg-muted/30 px-6 py-4 border-b flex items-center gap-2">
                    <Factory class="size-5" />
                    <h3 class="font-medium">Production Assignments</h3>
                </div>
                
                <div class="p-6">
                    <div v-if="user.role_slug === 'super'" class="text-center py-4">
                        <p class="text-muted-foreground">
                            <span class="inline-flex items-center gap-1 text-purple-600 dark:text-purple-400">
                                <Shield class="size-4" />
                                Super users
                            </span>
                            have access to all productions.
                        </p>
                    </div>
                    
                    <div v-else-if="user.productions.length === 0" class="text-center py-4">
                        <p class="text-muted-foreground">No productions assigned to this user.</p>
                        <button
                            class="mt-2 text-sm text-primary hover:underline"
                            @click="router.get(`/admin/users/${user.id}/edit`)"
                        >
                            Assign productions →
                        </button>
                    </div>
                    
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <div
                            v-for="production in user.productions"
                            :key="production.production_id"
                            class="flex items-center gap-3 p-3 rounded-md bg-muted/30"
                        >
                            <Factory class="size-4 text-muted-foreground" />
                            <span class="text-sm font-medium">{{ production.production_name }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
