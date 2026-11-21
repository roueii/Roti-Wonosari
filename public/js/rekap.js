/**
 * Rekap Bulanan & Rincian Harian: tombol "Lihat N ..." menampilkan / menyembunyikan baris tambahan
 * (elemen ber-kelas .is-extra di dalam container yang id-nya ada di data-toggle-extra).
 */
document.querySelectorAll('[data-toggle-extra]').forEach((btn) => {
    const container = document.getElementById(btn.dataset.toggleExtra);
    if (!container) return;
    btn.setAttribute('aria-controls', container.id);

    btn.addEventListener('click', () => {
        const expand = btn.getAttribute('aria-expanded') !== 'true';
        container.querySelectorAll('.is-extra').forEach((el) => { el.hidden = !expand; });
        btn.setAttribute('aria-expanded', String(expand));
        btn.querySelector('span').textContent = expand ? btn.dataset.less : btn.dataset.more;
    });
});
