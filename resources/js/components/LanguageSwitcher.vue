<script setup lang="ts">
import { useLocalization } from '@/composables/useLocalization';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Globe } from 'lucide-vue-next';

const { currentLocale, t, switchLanguage } = useLocalization();

const languages = [
    { code: 'en', label: 'English' },
    { code: 'id', label: 'Indonesian' },
];
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger :as-child="true">
            <Button
                variant="ghost"
                size="icon"
                class="h-9 w-9"
                :title="t('app.language')"
            >
                <Globe class="h-5 w-5" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-40">
            <DropdownMenuItem
                v-for="lang in languages"
                :key="lang.code"
                :as-child="true"
            >
                <button
                    @click="switchLanguage(lang.code as 'en' | 'id')"
                    class="w-full flex items-center gap-2 px-3 py-2 text-sm cursor-pointer"
                    :class="{
                        'bg-accent text-accent-foreground font-semibold':
                            currentLocale === lang.code,
                    }"
                >
                    <span>{{ lang.label }}</span>
                    <span
                        v-if="currentLocale === lang.code"
                        class="ml-auto text-xs"
                    >
                        ✓
                    </span>
                </button>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
