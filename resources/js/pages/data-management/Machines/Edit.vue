<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref, onMounted, computed } from 'vue';
import MachineGroupFormFields from '@/components/machine-group/MachineGroupFormFields.vue';
import InputConfigEditor from '@/components/machine-group/InputConfigEditor.vue';
import type { InputConfig } from '@/composables/useInputConfig';
import { useLocalization } from '@/composables/useLocalization';

const { t } = useLocalization();

const props = defineProps<{ 
    machineGroup: { 
        machine_group_id: number; 
        name: string; 
        description?: string;
        input_config?: InputConfig;
    } 
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('data_management.data_management'), href: '/data-management/production' },
    { title: t('data_management.machines'), href: '/data-management/machine' },
    { title: props.machineGroup?.name ?? t('data_management.edit'), href: window.location.pathname },
]);

const defaultConfig: InputConfig = {
    type: 'qty_only',
    fields: ['qty'],
    grade_types: [],
};

const form = useForm({
    name: props.machineGroup.name,
    description: props.machineGroup.description || '',
    input_config: props.machineGroup.input_config || defaultConfig,
});

const inputConfig = ref<InputConfig>(props.machineGroup.input_config || defaultConfig);

onMounted(() => {
    if (props.machineGroup.input_config) {
        inputConfig.value = props.machineGroup.input_config;
        form.input_config = props.machineGroup.input_config;
    }
});

function updateInputConfig(config: InputConfig): void {
    inputConfig.value = config;
    form.input_config = config;
}

function submit(): void {
    form.put(`/data-management/machine/${props.machineGroup.machine_group_id}`, {
        preserveState: false,
        onSuccess: () => {
            router.get('/data-management/machine');
        },
    });
}
</script>

<template>
    <Head :title="`${t('data_management.edit_machine')} — ${props.machineGroup.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="rounded-xl border border-sidebar-border/70 p-6">
                <h2 class="text-lg font-semibold">{{ t('data_management.edit_machine') }}</h2>
                <p class="mt-2 text-sm text-muted-foreground">{{ t('data_management.edit_machine_description') }}</p>

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
                            {{ form.processing ? t('data_management.saving') : t('data_management.save_changes') }}
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
