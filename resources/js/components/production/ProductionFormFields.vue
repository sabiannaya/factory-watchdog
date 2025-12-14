<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

interface Props {
    form: ReturnType<typeof useForm<{
        production_name: string;
        status: string;
    }>>;
}

defineProps<Props>();
</script>

<template>
    <div class="rounded-lg border border-sidebar-border/70 bg-card dark:bg-card p-6">
        <h2 class="text-lg font-semibold mb-5">Production Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium">Production Name <span class="text-red-500">*</span></label>
                <input 
                    v-model="form.production_name" 
                    type="text" 
                    class="input" 
                    required
                />
                <div v-if="form.errors.production_name" class="text-xs text-destructive dark:text-destructive">
                    {{ form.errors.production_name }}
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium">Status <span class="text-red-500">*</span></label>
                <select 
                    v-model="form.status" 
                    class="appearance-none rounded-md border bg-popover dark:bg-popover text-popover-foreground dark:text-popover-foreground px-3 py-2 pr-10 text-sm shadow-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                >
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <div v-if="form.errors.status" class="text-xs text-destructive dark:text-destructive">
                    {{ form.errors.status }}
                </div>
            </div>
        </div>
    </div>
</template>

