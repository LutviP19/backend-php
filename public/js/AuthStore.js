// Integrasi Service Worker pada AuthStore

const AuthStore = (function () {
    let _accessToken = null;
    let _swRegistration = null;

    // Inisialisasi & Register Service Worker
    async function _initServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                _swRegistration = await navigator.serviceWorker.register('/backend-php-sw.js');
                console.log('[Auth] Service Worker registered.');

                // Dengarkan pesan balasan dari Service Worker
                navigator.serviceWorker.addEventListener('message', (event) => {
                    const { type, payload } = event.data;

                    if (type === 'TOKEN_REFRESHED') {
                        console.log('[Auth] Menerima token baru dari Service Worker.');
                        AuthStore.setToken(payload.token, false); // Update memory tanpa reschedule SW berulang
                    } else if (type === 'AUTH_LOGOUT') {
                        AuthStore.clear();
                        window.location.href = '/login';
                    }
                });
            } catch (err) {
                console.error('[Auth] SW Registration Failed:', err);
            }
        }
    }

    // Kirim pesan ke Service Worker untuk menjadwalkan timer
    function _notifyServiceWorker(delayMs) {
        if (navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage({
                type: 'SCHEDULE_REFRESH',
                payload: { delayMs }
            });
        }
    }

    function _clearServiceWorkerTimer() {
        if (navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage({ type: 'CLEAR_REFRESH' });
        }
    }

    // Panggil init SW saat AuthStore dimuat
    _initServiceWorker();

    return {
        getToken() {
            return _accessToken;
        },

        setToken(token, notifySW = true) {
            if (!token) return;

            _accessToken = token;
            localStorage.setItem('is_logged_in', 'true');

            // Hitung delay 75% usia token
            const delay = _calculateRefreshDelay(token);

            // Delegasikan timer ke Service Worker agar tidak terkena throttling browser saat tab minimize
            if (notifySW) {
                _notifyServiceWorker(delay);
            }
        },

        clear() {
            _clearServiceWorkerTimer();
            _accessToken = null;
            localStorage.removeItem('is_logged_in');
        },

        // Manual refresh trigger (Fallback)
        async executeRefresh() {
            // Logika refresh biasa seperti sebelumnya...
        }
    };
})();