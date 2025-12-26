import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface HourlyInputFormData {
    production_machine_group_id: number | null;
    date: string;
    hour: number;
    output_qty_normal: number | null;
    output_qty_reject: number | null;
    output_grades: Record<string, number>;
    output_grade: string | null;
    output_ukuran: string | null;
    keterangan: string | null;
}

export default function useHourlyInputForm(hourlyInput?: any, inputConfig?: any) {
    const initial = hourlyInput ?? {};
    const inputConfigRef = ref(inputConfig ?? null);

    const preloadGrades = (gradesProp: any) => {
        const cfg = inputConfigRef.value;
        if (cfg?.grade_types && Array.isArray(cfg.grade_types) && cfg.grade_types.length) {
            const out: Record<string, number> = {};
            for (const g of cfg.grade_types) {
                out[g] = (gradesProp && gradesProp[g] != null) ? gradesProp[g] : 0;
            }
            return out;
        }
        return gradesProp ?? {};
    };

    const form = useForm<HourlyInputFormData>({
        production_machine_group_id: initial.production_machine_group_id ?? null,
        date: initial.date ?? new Date().toISOString().split('T')[0],
        hour: Number(initial.hour ?? 0),
        output_qty_normal: initial.output_qty_normal ?? initial.qty_normal ?? null,
        output_qty_reject: initial.output_qty_reject ?? initial.qty_reject ?? null,
        output_grades: preloadGrades(initial.output_grades ?? initial.grades ?? {}),
        output_grade: initial.output_grade ?? initial.grade ?? null,
        output_ukuran: initial.output_ukuran ?? initial.ukuran ?? null,
        keterangan: initial.keterangan ?? null,
    });

    const setInputConfig = (cfg: any) => {
        inputConfigRef.value = cfg ?? null;
        // reinitialize grades if grade types present
        form.output_grades = preloadGrades(form.output_grades ?? {});
    };

    const _snapshot = ref(JSON.stringify({
        date: form.date,
        hour: form.hour,
        output_qty_normal: form.output_qty_normal,
        output_qty_reject: form.output_qty_reject,
        output_grades: form.output_grades,
        output_grade: form.output_grade,
        output_ukuran: form.output_ukuran,
        keterangan: form.keterangan,
    }));

    const hasChanged = computed(() => {
        const current = JSON.stringify({
            date: form.date,
            hour: form.hour,
            output_qty_normal: form.output_qty_normal,
            output_qty_reject: form.output_qty_reject,
            output_grades: form.output_grades,
            output_grade: form.output_grade,
            output_ukuran: form.output_ukuran,
            keterangan: form.keterangan,
        });
        return current !== _snapshot.value;
    });

    const resetSnapshot = () => {
        _snapshot.value = JSON.stringify({
            date: form.date,
            hour: form.hour,
            output_qty_normal: form.output_qty_normal,
            output_qty_reject: form.output_qty_reject,
            output_grades: form.output_grades,
            output_grade: form.output_grade,
            output_ukuran: form.output_ukuran,
            keterangan: form.keterangan,
        });
    };

    return { form, hasChanged, resetSnapshot, setInputConfig };
}
