import { ref } from 'vue';

const SOUND_STORAGE_KEY = 'cms-order-alert-sound-enabled';

/**
 * Sound + desktop notification alert for newly-arrived orders.
 *
 * The chime is synthesized with the Web Audio API instead of an audio file
 * so the alert works without shipping/licensing a sound asset. Browsers
 * block AudioContext playback until the page has received a user gesture,
 * so `unlockAudio()` should be called from a click handler (e.g. mounted +
 * a document-wide one-time click listener) before the first chime.
 */
export default function useOrderAlert() {
    const soundEnabled = ref(localStorage.getItem(SOUND_STORAGE_KEY) !== '0');
    const notificationPermission = ref<NotificationPermission>(
        'Notification' in window ? Notification.permission : 'denied',
    );

    let audioCtx: AudioContext | null = null;

    const toggleSound = () => {
        soundEnabled.value = !soundEnabled.value;
        localStorage.setItem(SOUND_STORAGE_KEY, soundEnabled.value ? '1' : '0');
    };

    const unlockAudio = () => {
        audioCtx ??= new AudioContext();
        if (audioCtx.state === 'suspended') {
            audioCtx.resume().catch(() => {});
        }
    };

    const playChime = () => {
        if (!soundEnabled.value) return;

        try {
            audioCtx ??= new AudioContext();
            if (audioCtx.state === 'suspended') {
                audioCtx.resume().catch(() => {});
            }

            const now = audioCtx.currentTime;
            [880, 1175].forEach((freq, i) => {
                const start = now + i * 0.15;
                const osc = audioCtx!.createOscillator();
                const gain = audioCtx!.createGain();
                osc.type = 'sine';
                osc.frequency.value = freq;
                gain.gain.setValueAtTime(0, start);
                gain.gain.linearRampToValueAtTime(0.3, start + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.001, start + 0.3);
                osc.connect(gain).connect(audioCtx!.destination);
                osc.start(start);
                osc.stop(start + 0.35);
            });
        } catch {
            // Web Audio unavailable/blocked - fail silently, desktop notification still works.
        }
    };

    const requestNotificationPermission = async () => {
        if (!('Notification' in window)) return;
        notificationPermission.value = await Notification.requestPermission();
    };

    const showDesktopNotification = (
        id: string | number,
        title: string,
        body: string,
        onClick?: () => void,
    ) => {
        if (!('Notification' in window) || notificationPermission.value !== 'granted') {
            return;
        }

        try {
            const notification = new Notification(title, {
                body,
                icon: '/favicon.ico',
                tag: `order-${id}`,
            });

            notification.onclick = () => {
                window.focus();
                onClick?.();
                notification.close();
            };
        } catch {
            // Notification permission/environment can change mid-session
            // (e.g. revoked by the OS) - fail silently rather than blocking
            // the caller's own follow-up logic (e.g. advancing a poll cursor).
        }
    };

    return {
        soundEnabled,
        notificationPermission,
        toggleSound,
        unlockAudio,
        playChime,
        requestNotificationPermission,
        showDesktopNotification,
    };
}
