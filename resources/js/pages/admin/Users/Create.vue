<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref, computed, watch } from 'vue';
import { UserPlus, Shield, Users, Eye, EyeOff, ChevronDown, Wrench } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AlertDialog from '@/components/ui/alert-dialog/AlertDialog.vue';
import AlertDialogAction from '@/components/ui/alert-dialog/AlertDialogAction.vue';
import AlertDialogCancel from '@/components/ui/alert-dialog/AlertDialogCancel.vue';
import AlertDialogContent from '@/components/ui/alert-dialog/AlertDialogContent.vue';
import AlertDialogDescription from '@/components/ui/alert-dialog/AlertDialogDescription.vue';
import AlertDialogHeader from '@/components/ui/alert-dialog/AlertDialogHeader.vue';
import AlertDialogTitle from '@/components/ui/alert-dialog/AlertDialogTitle.vue';
import ToastNotifications from '@/components/ToastNotifications.vue';
import { useToast } from '@/composables/useToast';
import { useLocalization } from '@/composables/useLocalization';

const { t } = useLocalization();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('users.admin'), href: '/admin/users' },
    { title: t('users.users'), href: '/admin/users' },
    { title: t('users.create'), href: '/admin/users/create' },
]);

interface Role {
    role_id: number;
    name: string;
    slug: string;
}

interface Production {
    production_id: number;
    production_name: string;
}

const props = defineProps<{
    roles: Role[];
    productions: Production[];
}>();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_id: '' as number | string,
    production_ids: [] as number[],
    can_access_glue_spreaders: false,
    can_access_warehouse: false,
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const selectedRole = computed(() => {
    if (!form.role_id) return null;
    return props.roles.find(r => r.role_id === Number(form.role_id));
});

const selectedRoleLabel = computed(() => {
    if (!form.role_id) return t('users.select_role');
    const found = props.roles.find(r => r.role_id === Number(form.role_id));
    return found ? found.name : t('users.select_role');
});

const selectRole = (id: number | string) => {
    form.role_id = id;
};

const isStaffRole = computed(() => selectedRole.value?.slug === 'staff');

// Clear production assignments when switching to super role
watch(() => form.role_id, (newRoleId) => {
    const role = props.roles.find(r => r.role_id === Number(newRoleId));
    if (role?.slug === 'super') {
        form.production_ids = [];
    }
});

const { success, error } = useToast();

const showConfirmDialog = ref(false);
const submitting = ref(false);

const onSubmit = () => {
    showConfirmDialog.value = true;
};

const confirmSubmit = () => {
    submitting.value = true;
    form.post('/admin/users', {
        preserveScroll: true,
        onSuccess: () => {
            success(t('users.user_created'), t('users.user_created_description'));
            router.get('/admin/users');
        },
        onError: () => {
            error(t('users.create_failed'), t('users.create_failed_description'));
        },
        onFinish: () => {
            submitting.value = false;
            showConfirmDialog.value = false;
        },
    });
};

const toggleProduction = (productionId: number) => {
    const index = form.production_ids.indexOf(productionId);
    if (index === -1) {
        form.production_ids.push(productionId);
    } else {
        form.production_ids.splice(index, 1);
    }
};

const isProductionSelected = (productionId: number) => {
    return form.production_ids.includes(productionId);
};
</script>

<template>
    <Head :title="t('users.create_title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="mb-6">
                <h2 class="text-2xl font-semibold flex items-center gap-2">
                    <UserPlus class="size-6" />
                    {{ t('users.create_title') }}
                </h2>
                <p class="text-sm text-muted-foreground">{{ t('users.create_description') }}</p>
            </div>

            <form @submit.prevent="onSubmit" class="space-y-6">
                <!-- Basic Info -->
                <div class="rounded-lg border p-4 space-y-4">
                    <h3 class="font-medium text-lg">{{ t('users.basic_information') }}</h3>
                    
                    <div class="space-y-2">
                        <Label for="name">{{ t('users.name') }}</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            :placeholder="t('users.name_placeholder')"
                            :class="{ 'border-red-500': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="email">{{ t('users.email') }}</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            :placeholder="t('users.email_placeholder')"
                            :class="{ 'border-red-500': form.errors.email }"
                        />
                        <p v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</p>
                    </div>
                </div>

                <!-- Password -->
                <div class="rounded-lg border p-4 space-y-4">
                    <h3 class="font-medium text-lg">{{ t('users.password_section') }}</h3>
                    
                    <div class="space-y-2">
                        <Label for="password">{{ t('users.password_section') }}</Label>
                        <div class="relative">
                            <Input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                :placeholder="t('users.password_placeholder')"
                                class="pr-10"
                                :class="{ 'border-red-500': form.errors.password }"
                            />
                            <button
                                type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                                @click="showPassword = !showPassword"
                            >
                                <EyeOff v-if="showPassword" class="size-4" />
                                <Eye v-else class="size-4" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="text-sm text-red-500">{{ form.errors.password }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="password_confirmation">{{ t('users.confirm_new_password') }}</Label>
                        <div class="relative">
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                :placeholder="t('users.confirm_password_placeholder')"
                                class="pr-10"
                            />
                            <button
                                type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                                @click="showConfirmPassword = !showConfirmPassword"
                            >
                                <EyeOff v-if="showConfirmPassword" class="size-4" />
                                <Eye v-else class="size-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Role Selection -->
                <div class="rounded-lg border p-4 space-y-4">
                    <h3 class="font-medium text-lg flex items-center gap-2">
                        <Shield class="size-5" />
                        {{ t('users.role_permissions') }}
                    </h3>
                    
                    <div class="space-y-2">
                        <Label for="role">{{ t('users.role') }}</Label>
                        <DropdownMenu>
                            <DropdownMenuTrigger :as-child="true">
                                <button type="button" class="input w-full flex items-center justify-between" :class="{ 'border-red-500': form.errors.role_id }">
                                    <span class="truncate">{{ selectedRoleLabel }}</span>
                                    <ChevronDown class="ml-2 size-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-[var(--reka-dropdown-menu-trigger-width)] max-w-none">
                                <DropdownMenuItem :as-child="true">
                                    <button class="block w-full text-left px-3 py-2 text-sm" @click="selectRole('')">{{ t('users.select_role') }}</button>
                                </DropdownMenuItem>
                                <template v-for="role in roles" :key="role.role_id">
                                    <DropdownMenuItem :as-child="true">
                                        <button class="block w-full text-left px-3 py-2 text-sm" @click="selectRole(role.role_id)">{{ role.name }}</button>
                                    </DropdownMenuItem>
                                </template>
                            </DropdownMenuContent>
                        </DropdownMenu>
                        <p v-if="form.errors.role_id" class="text-sm text-red-500">{{ form.errors.role_id }}</p>
                        
                        <div v-if="selectedRole" class="mt-2 p-3 rounded-md bg-muted/50 text-sm">
                            <p v-if="selectedRole.slug === 'super'" class="text-purple-600 dark:text-purple-400">
                                <strong>Super:</strong> Full access to all features, including user management and deletion of resources.
                            </p>
                            <p v-else-if="selectedRole.slug === 'staff'" class="text-blue-600 dark:text-blue-400">
                                <strong>Staff:</strong> Can only access assigned productions. Can create, read, and update data but cannot delete.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Production Assignments (only for Staff) -->
                <div v-if="isStaffRole" class="rounded-lg border p-4 space-y-4">
                    <h3 class="font-medium text-lg flex items-center gap-2">
                        <Users class="size-5" />
                        {{ t('users.production_assignments') }}
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        {{ t('users.select_productions') }}
                    </p>
                    
                    <div v-if="productions.length === 0" class="text-sm text-muted-foreground italic">
                        No active productions available.
                    </div>
                    
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <label
                            v-for="production in productions"
                            :key="production.production_id"
                            class="flex items-center gap-3 p-3 rounded-md border transition-colors hover:bg-muted/50"
                            :class="{ 'bg-primary/10 border-primary': isProductionSelected(production.production_id) }"
                        >
                            <input
                                type="checkbox"
                                :checked="isProductionSelected(production.production_id)"
                                @change="toggleProduction(production.production_id)"
                                class="rounded border-gray-300 text-primary focus:ring-primary"
                            />
                            <span class="text-sm">{{ production.production_name }}</span>
                        </label>
                    </div>
                    
                    <p v-if="form.errors.production_ids" class="text-sm text-red-500">{{ form.errors.production_ids }}</p>
                </div>

                <!-- Glue Spreader Menu Access (only for Staff) -->
                <div v-if="isStaffRole" class="rounded-lg border p-4 space-y-4">
                    <h3 class="font-medium text-lg flex items-center gap-2">
                        <Wrench class="size-5" />
                        {{ t('users.glue_access') }}
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        {{ t('users.glue_access_description') }}
                    </p>

                    <label class="flex items-center gap-3 p-3 rounded-md border transition-colors hover:bg-muted/50">
                        <input
                            type="checkbox"
                            v-model="form.can_access_glue_spreaders"
                            class="rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <span class="text-sm">{{ t('users.glue_access') }}</span>
                    </label>
                </div>

                <!-- Warehouse Menu Access (only for Staff) -->
                <div v-if="isStaffRole" class="rounded-lg border p-4 space-y-4">
                    <h3 class="font-medium text-lg flex items-center gap-2">
                        <Wrench class="size-5" />
                        {{ t('users.warehouse_access') }}
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        {{ t('users.warehouse_access_description') }}
                    </p>

                    <label class="flex items-center gap-3 p-3 rounded-md border transition-colors hover:bg-muted/50">
                        <input
                            type="checkbox"
                            v-model="form.can_access_warehouse"
                            class="rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <span class="text-sm">{{ t('users.warehouse_access') }}</span>
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <button
                        type="button"
                        class="btn-secondary rounded-md px-4 py-2 transition-all duration-150 hover:opacity-90"
                        @click="router.get('/admin/users')"
                    >
                        {{ t('users.cancel') }}
                    </button>
                    <button
                        type="submit"
                        class="btn rounded-md px-4 py-2 transition-all duration-150 hover:opacity-90 disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? t('users.creating') : t('users.confirm_create') }}
                    </button>
                </div>
            </form>

            <AlertDialog :open="showConfirmDialog" @update:open="showConfirmDialog = $event">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>{{ t('users.confirm_create_title') }}</AlertDialogTitle>
                        <AlertDialogDescription>
                            {{ t('users.confirm_create_description') }}
                        </AlertDialogDescription>

                        <div class="mt-4 border-t pt-3">
                            <h3 class="text-sm font-medium mb-2">Preview</h3>
                            <div class="rounded-lg bg-muted/50 p-3 text-sm">
                                <div><strong>Name:</strong> {{ form.name || '-' }}</div>
                                <div><strong>Email:</strong> {{ form.email || '-' }}</div>
                                <div><strong>Role:</strong> {{ selectedRoleLabel }}</div>
                                <div class="mt-2">
                                    <strong>Productions:</strong>
                                    <div v-if="form.production_ids && form.production_ids.length" class="mt-1">
                                        <ul class="list-disc list-inside">
                                            <li v-for="p in productions.filter(pr => form.production_ids.includes(pr.production_id))" :key="p.production_id">{{ p.production_name }}</li>
                                        </ul>
                                    </div>
                                    <div v-else class="text-sm">None</div>
                                </div>
                            </div>
                        </div>
                    </AlertDialogHeader>

                    <div class="flex justify-end gap-2 mt-4">
                        <AlertDialogCancel>{{ t('users.cancel') }}</AlertDialogCancel>
                        <AlertDialogAction @click="confirmSubmit" :disabled="submitting" class="hover:cursor-pointer btn">{{ t('users.confirm_create') }}</AlertDialogAction>
                    </div>
                </AlertDialogContent>
            </AlertDialog>

            <ToastNotifications />

        </div>
    </AppLayout>
</template>
