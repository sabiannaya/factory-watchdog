<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref } from 'vue';
import MachineGroupFormFields from '@/components/machine-group/MachineGroupFormFields.vue';
import InputConfigEditor from '@/components/machine-group/InputConfigEditor.vue';
import type { InputConfig } from '@/composables/useInputConfig';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Data Management', href: '/data-management/production' },
    { title: 'Machine', href: '/data-management/machine' },
    { title: 'Create', href: window.location.pathname },
];

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
    <Head title="Create Machine Group" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="rounded-xl border border-sidebar-border/70 p-6">
                <h2 class="text-lg font-semibold">Create Machine Group</h2>
                <p class="mt-2 text-sm text-muted-foreground">Create a new machine group with custom input configuration</p>

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
                        <button type="submit" class="btn cursor-pointer" :disabled="form.processing">
                            {{ form.processing ? 'Creating...' : 'Create Machine Group' }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-ghost"
                            @click="router.get('/data-management/machine')"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
