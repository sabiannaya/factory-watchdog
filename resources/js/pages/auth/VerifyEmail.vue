<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { useLocalization } from '@/composables/useLocalization';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { send } from '@/routes/verification';
import { Form, Head } from '@inertiajs/vue3';

const { t } = useLocalization();

defineProps<{
    status?: string;
}>();
const showLogoutDialog = ref(false);

const handleLogout = (event?: Event) => {
    if (event) event.preventDefault();
    showLogoutDialog.value = true;
};

const confirmLogout = () => {
    router.post(logout.url());
    showLogoutDialog.value = false;
};

</script>

<template>
    <AuthLayout
        :title="t('auth.verify_title')"
        :description="t('auth.verify_description')"
    >
        <Head :title="t('auth.verify_head')" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ t('auth.verification_sent') }}
        </div>

        <Form
            v-bind="send.form()"
            class="space-y-6 text-center"
            v-slot="{ processing }"
        >
            <Button :disabled="processing" variant="secondary">
                <Spinner v-if="processing" />
                {{ t('auth.resend_verification') }}
            </Button>

            <TextLink
                :href="logout()"
                as="button"
                class="mx-auto block text-sm"
                @click.prevent="handleLogout"
            >
                {{ t('auth.logout') }}
            </TextLink>
        </Form>

        <AlertDialog :open="showLogoutDialog" @update:open="(v) => showLogoutDialog = v">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{{ t('auth.logout') }}</AlertDialogTitle>
                </AlertDialogHeader>

                <AlertDialogDescription>
                    {{ t('auth.logout_confirm') }}
                </AlertDialogDescription>

                <AlertDialogFooter>
                    <AlertDialogCancel>{{ t('auth.cancel') }}</AlertDialogCancel>
                    <AlertDialogAction @click="confirmLogout">{{ t('auth.logout') }}</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AuthLayout>
</template>
