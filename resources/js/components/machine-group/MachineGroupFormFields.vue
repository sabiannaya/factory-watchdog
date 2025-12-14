<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

interface Props {
    form: ReturnType<typeof useForm<{
        name: string;
        description: string;
        input_config?: any;
    }>>;
}

defineProps<Props>();
</script>

<template>
    <div class="space-y-4">
        <h3 class="text-sm font-medium">Basic Information</h3>
        
        <div class="grid gap-2">
            <label class="text-sm font-medium">Name <span class="text-red-500">*</span></label>
            <input
                v-model="form.name"
                type="text"
                class="input"
                required
            />
            <div v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</div>
        </div>

        <div class="grid gap-2">
            <label class="text-sm font-medium">Description</label>
            <textarea
                v-model="form.description"
                class="input min-h-[80px]"
            />
            <div v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</div>
        </div>
    </div>
</template>

