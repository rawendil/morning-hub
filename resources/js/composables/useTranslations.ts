import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import enTranslations from '../../../lang/en.json';

const translations: Record<string, Record<string, string>> = {
    en: enTranslations as Record<string, string>,
};

export function useTranslations() {
    const auth = useAuthStore();
    const locale = computed(() => auth.locale);

    function t(key: string, replacements?: Record<string, string>): string {
        const dict = translations[locale.value];
        let value = dict && dict[key] ? dict[key] : key;
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
