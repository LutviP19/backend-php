
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