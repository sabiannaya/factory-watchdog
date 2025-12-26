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
    { value: 'qty', label: 'Quantity (Qty)' },
    { value: 'qty_normal', label: 'Quantity Normal' },
    { value: 'qty_reject', label: 'Quantity Reject' },
    { value: 'grades', label: 'Grades (Multiple)' },
    { value: 'grade', label: 'Grade (Single)' },
    { value: 'ukuran', label: 'Size/Dimension (Ukuran)' },
    { value: 'keterangan', label: 'Description (Keterangan)' },
];

export function useInputConfig(initialConfig?: InputConfig) {
    const selectedFields = ref<string[]>(initialConfig?.fields || ['qty']);
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
        if (fields.length === 1 && fields[0] === 'qty') {
            return 'qty_only';
        }
        if (fields.includes('qty_normal') && fields.includes('qty_reject')) {
            return 'normal_reject';
        }
        if (fields.includes('grades')) {
            return 'grades';
        }
        if (fields.includes('grade') && fields.includes('qty')) {
            return 'grade_qty';
        }
        if (fields.includes('qty') && fields.includes('ukuran')) {
            return 'qty_ukuran';
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
        selectedFields.value = config?.fields || ['qty'];
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

