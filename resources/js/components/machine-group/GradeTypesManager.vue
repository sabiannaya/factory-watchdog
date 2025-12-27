<script setup lang="ts">
import { ref } from 'vue';

interface Props {
    gradeTypes: string[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'add', type: string): void;
    (e: 'remove', type: string): void;
}>();

const newGradeType = ref('');

function addGradeType(): void {
    if (newGradeType.value.trim() && !props.gradeTypes.includes(newGradeType.value.trim())) {
        emit('add', newGradeType.value.trim());
        newGradeType.value = '';
    }
}

function removeGradeType(type: string): void {
    emit('remove', type);
}
</script>

<template>
    <div class="space-y-3 p-4 rounded-md border bg-muted/20">
        <label class="text-sm font-medium">Grade Types</label>
        <p class="text-xs text-muted-foreground">Define the types of grades (e.g., Faceback, OPC, PPC)</p>
        
        <div class="flex gap-2">
            <input
                v-model="newGradeType"
                type="text"
                placeholder="Add grade type..."
                class="input flex-1"
                @keyup.enter="addGradeType"
            />
            <button
                type="button"
                @click="addGradeType"
                class="hover:cursor-pointer btn"
            >
                Add
            </button>
        </div>

        <div v-if="gradeTypes.length > 0" class="flex flex-wrap gap-2">
            <span
                v-for="type in gradeTypes"
                :key="type"
                class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-primary/10 text-primary text-sm"
            >
                {{ type }}
                <button
                    type="button"
                    @click="removeGradeType(type)"
                    class="hover:text-primary/70"
                >
                    ×
                </button>
            </span>
        </div>
    </div>
</template>

