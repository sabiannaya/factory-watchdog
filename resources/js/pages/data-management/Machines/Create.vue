<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';
import MachineGroupFormFields from '@/components/machine-group/MachineGroupFormFields.vue';
import InputConfigEditor from '@/components/machine-group/InputConfigEditor.vue';
import type { InputConfig } from '@/composables/useInputConfig';
import { useLocalization } from '@/composables/useLocalization';

const { t } = useLocalization();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('data_management.data_management'), href: '/data-management/production' },
    { title: t('data_management.machines'), href: '/data-management/machine' },
    { title: t('data_management.create'), href: window.location.pathname },
]);

const initialConfig: InputConfig = {
    type: 'qty_only',
    fields: ['qty'],
    grade_types: [],
};

const form = useForm({
    name: '',
    description: '',
    input_config: initialConfig,
});

const inputConfig = ref<InputConfig>(initialConfig);

function updateInputConfig(config: InputConfig): void {
    inputConfig.value = config;
    form.input_config = config;
}

function submit(): void {
    form.post('/data-management/machine', {
        preserveState: false,
        onSuccess: () => {
            router.get('/data-management/machine');
        },
    });
}
</script>

<template>
    <Head :title="t('data_management.create_machine')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="rounded-xl border border-sidebar-border/70 p-6">
                <h2 class="text-lg font-semibold">{{ t('data_management.create_machine') }}</h2>
                <p class="mt-2 text-sm text-muted-foreground">{{ t('data_management.create_machine_description') }}</p>

                <form @submit.prevent="submit" class="mt-6 space-y-6">
                    <MachineGroupFormFields :form="form" />

                    <InputConfigEditor
                        v-model="inputConfig"
                        :show-preview="true"
                        :show-errors="true"
                        :error-message="form.errors['input_config']"
                        @update:model-value="updateInputConfig"
                    />

                    <div class="flex items-center gap-3">
                        <button type="submit" class="hover:cursor-pointer btn" :disabled="form.processing">
                            {{ form.processing ? t('data_management.creating') : t('data_management.create_machine') }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-ghost"
                            @click="router.get('/data-management/machine')"
                        >
                            {{ t('data_management.cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
