import { ref, computed, type Ref } from 'vue';

export interface InputConfig {
    type: string;
    fields: string[];
    grade_types?: string[];
}

export interface InputField {
    value: string;
    label: string;
}

export const AVAILABLE_FIELDS: InputField[] = [
    { value: 'qty_normal', label: 'Quantity Normal' },
    { value: 'qty_reject', label: 'Quantity Reject' },
    { value: 'grades', label: 'Grades (Multiple)' },
    { value: 'grade', label: 'Grade (Single)' },
    { value: 'ukuran', label: 'Size/Dimension (Ukuran)' },
    { value: 'keterangan', label: 'Description (Keterangan)' },
];

const DEFAULT_FIELDS = ['qty_normal', 'qty_reject'];

function normalizeFields(fields?: string[]): string[] {
    const normalized = (fields || []).filter((field) => field !== 'qty');

    if (!normalized.includes('qty_normal')) {
        normalized.push('qty_normal');
    }

    if (!normalized.includes('qty_reject')) {
        normalized.push('qty_reject');
    }

    return normalized;
}

export function useInputConfig(initialConfig?: InputConfig) {
    const selectedFields = ref<string[]>(normalizeFields(initialConfig?.fields || DEFAULT_FIELDS));
    const gradeTypes = ref<string[]>(initialConfig?.grade_types || []);
    const newGradeType = ref('');

    const hasGrades = computed(() => selectedFields.value.includes('grades') || selectedFields.value.includes('grade'));

    function toggleField(field: string): void {
        const index = selectedFields.value.indexOf(field);
        if (index > -1) {
            selectedFields.value.splice(index, 1);
        } else {
            // Don't allow both 'grade' and 'grades' at the same time
            if (field === 'grade' && selectedFields.value.includes('grades')) {
                selectedFields.value.splice(selectedFields.value.indexOf('grades'), 1);
            } else if (field === 'grades' && selectedFields.value.includes('grade')) {
                selectedFields.value.splice(selectedFields.value.indexOf('grade'), 1);
            }
            selectedFields.value.push(field);
        }
    }

    function addGradeType(): void {
        if (newGradeType.value.trim() && !gradeTypes.value.includes(newGradeType.value.trim())) {
            gradeTypes.value.push(newGradeType.value.trim());
            newGradeType.value = '';
        }
    }

    function removeGradeType(type: string): void {
        const index = gradeTypes.value.indexOf(type);
        if (index > -1) {
            gradeTypes.value.splice(index, 1);
        }
    }

    function determineType(fields: string[]): string {
        const normalized = normalizeFields(fields);

        if (normalized.includes('qty_normal') && normalized.includes('qty_reject')) {
            return 'normal_reject';
        }
        if (normalized.includes('grades')) {
            return 'grades';
        }
        if (normalized.includes('grade') && normalized.includes('qty_normal')) {
            return 'grade_qty_normal';
        }
        if (normalized.includes('qty_normal') && normalized.includes('ukuran')) {
            return 'qty_normal_ukuran';
        }
        return 'custom';
    }

    function buildInputConfig(): InputConfig {
        return {
            type: determineType(selectedFields.value),
            fields: [...selectedFields.value],
            grade_types: hasGrades.value ? [...gradeTypes.value] : [],
        };
    }

    function reset(config?: InputConfig): void {
        selectedFields.value = normalizeFields(config?.fields || DEFAULT_FIELDS);
        gradeTypes.value = config?.grade_types || [];
        newGradeType.value = '';
    }

    return {
        selectedFields,
        gradeTypes,
        newGradeType,
        hasGrades,
        toggleField,
        addGradeType,
        removeGradeType,
        determineType,
        buildInputConfig,
        reset,
    };
}

