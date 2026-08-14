
document.body.addEventListener('htmx:configRequest', (event) => {
    // Cek apakah elemen pengirim memuat atribut data-idempotency-prefix
    const element = event.detail.elt;
    const prefix = element.dataset.idempotencyPrefix;

    if (prefix) {
        // Generate key secara aman tanpa eval()
        const timestamp = Date.now();
        const randomStr = Math.random().toString(36).substring(2, 9);
        const idempotencyKey = `${prefix}-${timestamp}-${randomStr}`;

        // Inject ke header request HTMX secara native
        event.detail.headers['X-Idempotency-Key'] = idempotencyKey;
    }
});

document.body.addEventListener('htmx:afterOnLoad', function(evt) {
    // Cari elemen yang punya x-data dengan fungsi updatePath
    const sidebar = document.querySelector('[x-data]');
    if (sidebar && sidebar.__x_data_stack) {
        // Panggil fungsi updatePath milik Alpine secara manual
        sidebar._x_dataStack[0].updatePath();
    }
});

// ==========================================
// LISTENER IDLE / INACTIVITY TIMEOUT (30 MIN)
// ==========================================
(function() {
    const IDLE_TIMEOUT_MS = 30 * 60 * 1000; // 30 Menit dalam Milidetik
    let idleTimer;

    function resetIdleTimer() {
        clearTimeout(idleTimer);
        
        idleTimer = setTimeout(() => {
            // Lakukan reload / redirect ke /dashboard atau /home
            window.location.href = '/dashboard'; 
            // Jika ingin reload ke halaman saat ini: window.location.reload();
        }, IDLE_TIMEOUT_MS);
    }

    // Event DOM standar untuk melacak interaksi pengguna
    const activityEvents = [
        'mousemove', 
        'keydown', 
        'click', 
        'scroll', 
        'touchstart'
    ];

    // Mendaftarkan event listener untuk setiap interaksi pengguna
    activityEvents.forEach(eventName => {
        window.addEventListener(eventName, resetIdleTimer, { passive: true });
    });

    // Reset timer juga saat ada request HTMX (interaksi AJAX aktif)
    document.body.addEventListener('htmx:afterRequest', resetIdleTimer);

    // Jalankan timer pertama kali saat halaman dimuat
    resetIdleTimer();
})();