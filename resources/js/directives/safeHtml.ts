import DOMPurify from 'dompurify';
import type { Directive } from 'vue';

const sanitize = (value: unknown): string => {
    if (typeof value !== 'string') {
        return '';
    }

    return DOMPurify.sanitize(value, { USE_PROFILES: { html: true, svg: true } });
};

export const safeHtml: Directive<HTMLElement, string> = {
    mounted(el, binding) {
        el.innerHTML = sanitize(binding.value);
    },
    updated(el, binding) {
        if (binding.value !== binding.oldValue) {
            el.innerHTML = sanitize(binding.value);
        }
    },
};
