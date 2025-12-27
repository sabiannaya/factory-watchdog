<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
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
        title="Verify email"
        description="Please verify your email address by clicking on the link we just emailed to you."
    >
        <Head title="Email verification" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            A new verification link has been sent to the email address you
            provided during registration.
        </div>

        <Form
            v-bind="send.form()"
            class="space-y-6 text-center"
            v-slot="{ processing }"
        >
            <Button :disabled="processing" variant="secondary">
                <Spinner v-if="processing" />
                Resend verification email
            </Button>

            <TextLink
                :href="logout()"
                as="button"
                class="mx-auto block text-sm"
                @click.prevent="handleLogout"
            >
                Log out
            </TextLink>
        </Form>

        <AlertDialog :open="showLogoutDialog" @update:open="(v) => showLogoutDialog = v">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Log out</AlertDialogTitle>
                </AlertDialogHeader>

                <AlertDialogDescription>
                    Are you sure you want to log out?
                </AlertDialogDescription>

                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="confirmLogout">Log out</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AuthLayout>
</template>
