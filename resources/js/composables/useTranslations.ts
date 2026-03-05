import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useTranslations() {
    const page = usePage();
    const translations = computed(
        () => page.props.translations as Record<string, string>,
    );
    const locale = computed(() => page.props.locale as string);

    function t(
        key: string,
        replacements?: Record<string, string>,
    ): string {
        let value = translations.value[key] ?? key;

        if (replacements) {
            for (const [placeholder, replacement] of Object.entries(
                replacements,
            )) {
                value = value.replace(`:${placeholder}`, replacement);
            }
        }

        return value;
    }

    return { t, locale };
}
