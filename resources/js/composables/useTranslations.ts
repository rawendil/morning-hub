import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';

export function useTranslations() {
    const auth = useAuthStore();
    const locale = computed(() => auth.locale);

    function t(key: string, replacements?: Record<string, string>): string {
        if (!replacements) { return key; }
        let value = key;
        for (const [placeholder, replacement] of Object.entries(replacements)) {
            value = value.replace(`:${placeholder}`, replacement);
        }
        return value;
    }

    return { t, locale };
}
