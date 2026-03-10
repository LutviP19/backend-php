window.addEventListener('offline', function() {
    Swal.fire('Koneksi Putus', 'Anda sedang offline. Data mungkin tidak tersimpan.', 'error');
});

window.addEventListener('online', function() {
    Swal.fire('Kembali Online', 'Koneksi terhubung. Silakan lanjutkan.', 'success');
});

// Meskipun HTMX punya 'delay:500ms', JS ini berguna jika Anda 
// ingin melakukan validasi client-side sebelum request dikirim
document.body.addEventListener('htmx:beforeRequest', function(evt) {
    const searchInput = document.querySelector('#search-input');
    if (evt.target === searchInput && searchInput.value.length < 3) {
        // Jangan kirim request jika karakter kurang dari 3
        evt.preventDefault();
    }
});

document.body.addEventListener('htmx:afterSwap', function(evt) {
    // Cari input dengan atribut 'autofocus' di dalam konten yang baru dimuat
    const firstInput = evt.detail.elt.querySelector('[autofocus]');
    if (firstInput) {
        firstInput.focus();
    }
});

document.addEventListener('keydown', function(e) {
    // Alt + N untuk memicu tombol tambah barang
    if (e.altKey && e.key === 'n') {
        const btn = document.querySelector('#btn-tambah-barang');
        if (btn) btn.click();
    }
});

// Integrasi HTMX dengan SweetAlert2
document.body.addEventListener('htmx:confirm', function(evt) {
    // Hanya berlaku jika elemen memiliki atribut hx-confirm-sweet
    if (evt.target.hasAttribute('hx-confirm-sweet')) {
        evt.preventDefault(); // Hentikan request HTMX sementara
        
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: evt.target.getAttribute('hx-confirm-sweet'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                evt.detail.issueRequest(); // Lanjutkan request HTMX
            }
        });
    }
});

// Cara pakai di HTML: <button hx-delete="/hapus/1" hx-confirm-sweet="Data traktor akan dihapus permanen">...</button>

// Gunakan class .input-rupiah pada input Anda
document.body.addEventListener('input', function (e) {
    if (e.target.classList.contains('input-rupiah')) {
        let value = e.target.value.replace(/\D/g, '');
        e.target.value = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(value);
    }
});
