<script setup lang="ts">
const props = defineProps<{
    machineGroupId: number;
    machineGroupName: string;
}>();

// Mock data - in production this would come from an API call
const assignments = [
    {
        production_name: 'Production Line 1',
        machine_count: 5,
        status: 'active',
    },
    {
        production_name: 'Production Line 2',
        machine_count: 3,
        status: 'active',
    },
];
</script>

<template>
    <div class="rounded-xl border border-sidebar-border/70 p-4 bg-card">
        <h4 class="text-sm font-semibold mb-3">Production Assignments - {{ machineGroupName }}</h4>
        <div v-if="assignments.length === 0" class="text-sm text-muted-foreground text-center py-4">
            No assignments
        </div>
        <div v-else class="space-y-2">
            <div 
                v-for="(assignment, idx) in assignments" 
                :key="idx"
                class="flex items-center justify-between p-2 rounded-lg bg-sidebar-border/5 hover:bg-sidebar-border/10"
            >
                <div class="flex-1">
                    <p class="text-sm font-medium">{{ assignment.production_name }}</p>
                    <p class="text-xs text-muted-foreground">{{ assignment.machine_count }} machines</p>
                </div>
                <span 
                    class="text-xs px-2 py-1 rounded-full"
                    :class="{
                        'bg-green-100 text-green-700': assignment.status === 'active',
                        'bg-gray-100 text-gray-700': assignment.status === 'inactive',
                    }"
                >
                    {{ assignment.status }}
                </span>
            </div>
        </div>
    </div>
</template>
