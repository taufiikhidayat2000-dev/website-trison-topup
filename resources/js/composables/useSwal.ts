import Swal, { type SweetAlertOptions } from 'sweetalert2';

// Confirm-button text/title patterns that should render as the destructive
// (red) variant instead of the default primary blue-cyan gradient. This lets
// every existing confirm() call site across the app pick up the right button
// color automatically, without having to touch each one individually.
const DANGER_PATTERN = /delete|hapus|reject|tolak|remove/i;

function isDangerAction(options: SweetAlertOptions): boolean {
    const text = [options.title, options.confirmButtonText]
        .filter((value): value is string => typeof value === 'string')
        .join(' ');

    return DANGER_PATTERN.test(text);
}

export function useSwal() {
    const confirm = (options: SweetAlertOptions = {}) => {
        const defaultOptions: SweetAlertOptions = {
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            reverseButtons: true,
            buttonsStyling: false,
        };

        const merged = { ...defaultOptions, ...options };
        const confirmButtonClass = isDangerAction(merged)
            ? 'swal-btn-danger'
            : '';

        return Swal.fire({
            ...merged,
            customClass: {
                confirmButton: confirmButtonClass,
                ...(options.customClass as Record<string, string> | undefined),
            },
        } as SweetAlertOptions);
    };

    const toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        },
    });

    /**
     * Show a blocking loading popup with a themed spinner (never the
     * browser's native loading indicator). Call `close()` to dismiss it.
     */
    const loading = (text: string = 'Sedang memproses...') => {
        Swal.fire({
            title: text,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });
    };

    const close = () => Swal.close();

    return {
        confirm,
        toast,
        loading,
        close,
    };
}
