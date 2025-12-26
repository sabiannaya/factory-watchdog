<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Eye, Edit2, Trash2, UserPlus, Shield, Users, ChevronDown } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
} from '@/components/ui/dropdown-menu';
import IconActionButton from '@/components/ui/IconActionButton.vue';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import ToastNotifications from '@/components/ToastNotifications.vue';
import { useToast } from '@/composables/useToast';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/users' },
    { title: 'Users', href: '/admin/users' },
];

const loading = ref(false);
const { toasts, dismiss: removeToast } = useToast();

interface UserRow {
    id: number;
    name: string;
    email: string;
    role_id: number | null;
    role_name: string;
    role_slug: string | null;
    productions_count: number;
    created_at: string;
}

interface Role {
    role_id: number;
    name: string;
    slug: string;
}

const props = defineProps<{
    users?: {
        data: UserRow[];
        next_cursor?: string | null;
        prev_cursor?: string | null;
    };
    roles?: Role[];
    meta?: {
        sort?: string;
        direction?: string;
        q?: string;
        per_page?: number;
        role_id?: number | string;
    };
}>();

const dataSource = computed(() => props.users?.data ?? []);
const roles = computed(() => props.roles ?? []);
const search = ref(props.meta?.q ?? '');
const selectedRole = ref<number | string>(props.meta?.role_id ?? '');
let searchTimer: ReturnType<typeof setTimeout> | undefined;

const selectedRoleLabel = computed(() => {
    if (!selectedRole.value) return 'All Roles';
    const found = roles.value.find(r => String(r.role_id) === String(selectedRole.value));
    return found ? found.name : 'All Roles';
});

const selectRole = (id: number | string) => {
    selectedRole.value = id || '';
    onRoleChange();
};

const triggerSearch = () => {
    const perPage = props.meta?.per_page ?? 10;
    router.get('/admin/users', {
        q: search.value || null,
        role_id: selectedRole.value || null,
        per_page: perPage,
    }, { preserveState: true, replace: true });
};

const onRoleChange = () => {
    const perPage = props.meta?.per_page ?? 10;
    router.get('/admin/users', {
        q: search.value || null,
        role_id: selectedRole.value || null,
        per_page: perPage,
    }, { preserveState: true, replace: true });
};

// Watch for search changes with debounce
import { watch } from 'vue';
watch(search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(triggerSearch, 500);
});

const goNext = () => {
    const next = props.users?.next_cursor;
    if (!next) return;
    router.get('/admin/users', {
        cursor: next,
        role_id: selectedRole.value || null,
        q: search.value || null,
    }, { preserveState: true, replace: true });
};

const goPrev = () => {
    const prev = props.users?.prev_cursor;
    if (!prev) return;
    router.get('/admin/users', {
        cursor: prev,
        role_id: selectedRole.value || null,
        q: search.value || null,
    }, { preserveState: true, replace: true });
};

const goDetail = (id: number) => router.get(`/admin/users/${id}`);
const goEdit = (id: number) => router.get(`/admin/users/${id}/edit`);

const confirmDelete = (id: number) => {
    if (!window.confirm('Are you sure you want to delete this user?')) return;
    router.delete(`/admin/users/${id}`, { preserveState: false });
};

// Inertia event listeners - store references but don't try to remove them
// (Inertia doesn't support removing event listeners)
onMounted(() => {
    router.on('start', () => (loading.value = true));
    router.on('finish', () => (loading.value = false));
});

// No cleanup needed - Inertia manages its own event listeners

const getRoleBadgeClass = (slug: string | null) => {
    if (slug === 'super') return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
    if (slug === 'staff') return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};
</script>

<template>
    <Head title="User Management" />
    <ToastNotifications :toasts="toasts" @dismiss="removeToast" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold flex items-center gap-2">
                        <Users class="size-6" />
                        User Management
                    </h2>
                    <p class="text-sm text-muted-foreground">Manage users, roles, and production assignments</p>
                </div>
                <button
                    @click="router.get('/admin/users/create')"
                    class="btn flex items-center gap-2 rounded-md shadow-sm transition-all duration-150 hover:opacity-90 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black/10 dark:focus:ring-white/20"
                >
                    <UserPlus class="size-4" />
                    Add User
                </button>
            </div>

            <!-- Filters -->
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Role Filter</label>
                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <button type="button" class="input w-full flex items-center justify-between">
                                <span class="truncate">{{ selectedRoleLabel }}</span>
                                <ChevronDown class="ml-2 size-4" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="min-w-[12rem]">
                            <DropdownMenuItem :as-child="true">
                                <button class="block w-full text-left px-3 py-2 text-sm" @click="selectRole('')">All Roles</button>
                            </DropdownMenuItem>
                            <template v-for="role in roles" :key="role.role_id">
                                <DropdownMenuItem :as-child="true">
                                    <button class="block w-full text-left px-3 py-2 text-sm" @click="selectRole(role.role_id)">{{ role.name }}</button>
                                </DropdownMenuItem>
                            </template>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Search</label>
                    <div class="relative">
                        <Input
                            v-model="search"
                            :disabled="loading"
                            type="search"
                            placeholder="Search by name or email..."
                            class="w-full"
                        />
                        <Spinner v-if="loading" class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground" />
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="mt-4">
                <div class="overflow-x-auto rounded-lg border">
                    <table class="w-full table-auto border-collapse">
                        <thead class="bg-muted/50">
                            <tr class="text-left">
                                <th class="px-4 py-3 text-sm font-medium">Name</th>
                                <th class="px-4 py-3 text-sm font-medium">Email</th>
                                <th class="px-4 py-3 text-sm font-medium">Role</th>
                                <th class="px-4 py-3 text-sm font-medium">Assigned Productions</th>
                                <th class="px-4 py-3 text-sm font-medium">Created</th>
                                <th class="px-4 py-3 text-sm font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in dataSource" :key="user.id" class="border-t hover:bg-muted/30 transition-colors">
                                <td class="px-4 py-3 text-sm font-medium">{{ user.name }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ user.email }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span 
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium"
                                        :class="getRoleBadgeClass(user.role_slug)"
                                    >
                                        <Shield v-if="user.role_slug === 'super'" class="size-3" />
                                        {{ user.role_name }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span v-if="user.role_slug === 'super'" class="text-muted-foreground italic">All</span>
                                    <span v-else>{{ user.productions_count }} production(s)</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ user.created_at }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex items-center gap-3">
                                        <IconActionButton :icon="Eye" label="View" color="blue" :onClick="() => goDetail(user.id)" />
                                        <IconActionButton :icon="Edit2" label="Edit" color="amber" :onClick="() => goEdit(user.id)" />
                                        <IconActionButton :icon="Trash2" label="Delete" color="red" :onClick="() => confirmDelete(user.id)" />
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="dataSource.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    No users found. Click "Add User" to create one.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex items-center justify-end gap-2">
                    <button 
                        class="btn-secondary rounded-md px-4 py-2 text-sm transition-all duration-150 hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed" 
                        :disabled="!props.users?.prev_cursor || loading" 
                        @click="goPrev"
                    >
                        Previous
                    </button>
                    <button 
                        class="btn-secondary rounded-md px-4 py-2 text-sm transition-all duration-150 hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed" 
                        :disabled="!props.users?.next_cursor || loading" 
                        @click="goNext"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>