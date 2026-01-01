<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { edit } from '@/routes/profile';
import type { User } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Settings } from 'lucide-vue-next';
import { useLocalization } from '@/composables/useLocalization';

const { t } = useLocalization();

interface Props {
    user: User;
}

const logout = () => {
    router.post('/logout', {}, {
        onFinish: () => {
            // Force navigation regardless of response status
            window.location.href = '/';
        },
        onError: () => {
            // Even if there's an error, redirect to home
            window.location.href = '/';
        }
    });
};

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" :href="edit()" prefetch as="button">
                <Settings class="mr-2 h-4 w-4" />
                {{ t('app.settings') }}
            </Link>
        </DropdownMenuItem>

        <DropdownMenuItem :as-child="true">
            <button class="block w-full text-left cursor-pointer" @click="logout">
                <LogOut class="mr-2 h-4 w-4" />
                {{ t('app.logout') }}
            </button>
        </DropdownMenuItem>
    </DropdownMenuGroup>
</template>