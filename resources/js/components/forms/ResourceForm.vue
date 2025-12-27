<script setup lang="ts">
import { reactive, toRefs, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

/**
 * Props
 * - fields: Array of field definitions { key, label, type, required?, options? }
 * - initial: initial data object
 * - action: form action url
 * - method: http method (post, put, patch)
 * - mode: 'create' | 'edit' | 'show'
 */
const props = defineProps<{
    fields: Array<{ key: string; label: string; type?: string; required?: boolean; options?: Array<{ value: any; label: string }> }>;
    initial?: Record<string, any>;
    action: string;
    method?: 'post' | 'put' | 'patch';
    mode?: 'create' | 'edit' | 'show';
}>();

const emits = defineEmits<{
    (e: 'submitted', payload: any): void;
}>();

const mode = props.mode ?? 'edit';

// build initial form data from fields
const dataInit: Record<string, any> = {};
props.fields.forEach((f) => {
    dataInit[f.key] = props.initial && props.initial[f.key] !== undefined ? props.initial[f.key] : (f.type === 'number' ? null : '');
});

const form = useForm({ ...dataInit });

const isShow = computed(() => mode === 'show');

function submit() {
    const method = (props.method ?? 'post').toLowerCase();
    const options: Record<string, any> = {};

    // For put/patch we send with _method where necessary using visit
    if (method === 'post') {
        form.post(props.action, {
            preserveState: false,
            onSuccess: (page) => emits('submitted', page),
        });
    } else if (method === 'put' || method === 'patch') {
        form.put(props.action, {
            preserveState: false,
            onSuccess: (page) => emits('submitted', page),
        });
    }
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-4">
        <div v-for="field in fields" :key="field.key" class="grid gap-1">
            <label class="text-sm font-medium text-muted-foreground">{{ field.label }}</label>

            <template v-if="field.type === 'textarea'">
                <textarea
                    v-model="form[field.key]"
                    :disabled="isShow"
                    class="input min-h-[80px]"
                />
            </template>

            <template v-else-if="field.type === 'select'">
                <select
                    v-model="form[field.key]"
                    :disabled="isShow"
                    aria-label="{{ field.label }}"
                    class="appearance-none rounded-md border bg-popover text-popover-foreground px-3 py-2 pr-10 text-sm shadow-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                >
                    <option v-for="opt in field.options ?? []" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
            </template>

            <template v-else-if="field.type === 'date'">
                <input type="date" v-model="form[field.key]" :disabled="isShow" class="input" />
            </template>

            <template v-else>
                <input
                    :type="field.type ?? 'text'"
                    v-model="form[field.key]"
                    :disabled="isShow"
                    class="input"
                />
            </template>

        </div>

        <div class="flex items-center gap-3" v-if="!isShow">
            <button type="submit" class="hover:cursor-pointer btn">Save</button>
            <button type="button" class="btn btn-ghost" @click="form.reset()">Reset</button>
        </div>
    </form>
</template>
