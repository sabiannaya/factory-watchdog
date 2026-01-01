<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { useLocalization } from '@/composables/useLocalization';
import { ref, computed } from 'vue';

const { t, currentLocale, switchLanguage } = useLocalization();

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    {
        title: t('settings.language'),
        href: '/settings/language',
    },
]);

const selectedLanguage = ref(currentLocale.value);
const saving = ref(false);

const languages = [
    { code: 'en', name: 'English', nativeName: 'English' },
    { code: 'id', name: 'Indonesian', nativeName: 'Bahasa Indonesia' },
];

const handleLanguageChange = async (langCode: string) => {
    selectedLanguage.value = langCode;
    saving.value = true;
    await switchLanguage(langCode as 'en' | 'id');
    saving.value = false;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('settings.language')" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    :title="t('settings.language')"
                    :description="t('settings.language_description')"
                />

                <div class="space-y-4">
                    <div class="grid gap-3">
                        <div
                            v-for="lang in languages"
                            :key="lang.code"
                            class="flex items-center justify-between rounded-lg border p-4 cursor-pointer transition-colors"
                            :class="{
                                'border-primary bg-primary/5': selectedLanguage === lang.code,
                                'hover:bg-muted/50': selectedLanguage !== lang.code,
                            }"
                            @click="handleLanguageChange(lang.code)"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full text-lg font-semibold"
                                    :class="{
                                        'bg-primary text-primary-foreground': selectedLanguage === lang.code,
                                        'bg-muted': selectedLanguage !== lang.code,
                                    }"
                                >
                                    {{ lang.code.toUpperCase() }}
                                </div>
                                <div>
                                    <p class="font-medium">{{ lang.nativeName }}</p>
                                    <p class="text-sm text-muted-foreground">{{ lang.name }}</p>
                                </div>
                            </div>
                            <div
                                v-if="selectedLanguage === lang.code"
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="3"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="h-4 w-4"
                                >
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <p class="text-sm text-muted-foreground">
                        {{ t('settings.language_note') }}
                    </p>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
