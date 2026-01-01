import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export interface Translations {
    app: Record<string, string>;
    auth: Record<string, string>;
    nav: Record<string, string>;
    input: Record<string, string>;
    settings: Record<string, string>;
    dashboard: Record<string, string>;
    logs: Record<string, string>;
    summary: Record<string, string>;
}

export function useLocalization() {
    const page = usePage();

    const currentLocale = computed(() => page.props.locale as string);
    const translations = computed(() => page.props.translations as Translations);

    const t = (key: string, params?: Record<string, string | number>): string => {
        const parts = key.split('.');
        let value: any = translations.value;

        for (const part of parts) {
            if (typeof value === 'object' && value !== null && part in value) {
                value = value[part];
            } else {
                return key;
            }
        }

        let result = typeof value === 'string' ? value : key;

        // Replace :param placeholders with values
        if (params) {
            for (const [paramKey, paramValue] of Object.entries(params)) {
                result = result.replace(new RegExp(`:${paramKey}`, 'g'), String(paramValue));
            }
        }

        return result;
    };

    const switchLanguage = async (locale: 'en' | 'id') => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const response = await fetch('/language/switch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
                body: JSON.stringify({ language: locale }),
            });

            if (response.ok) {
                location.reload();
            }
        } catch (error) {
            console.error('Error switching language:', error);
        }
    };

    return {
        currentLocale,
        translations,
        t,
        switchLanguage,
    };
}
