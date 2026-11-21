// Kartu "Unggah Bukti Foto": daftar file terpilih, hapus file, pratinjau foto & pop-up contoh foto.
document.querySelectorAll('[data-upload]').forEach((card) => {
    const input = card.querySelector('[data-upload-input]');
    const list = card.querySelector('[data-upload-list]');
    const errorEl = card.querySelector('[data-upload-error]');
    const MAX = 5 * 1024 * 1024;
    let files = [];
    let opener = null;

    const imageIcon = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M21 3.6v16.8a.6.6 0 0 1-.6.6H3.6a.6.6 0 0 1-.6-.6V3.6a.6.6 0 0 1 .6-.6h16.8a.6.6 0 0 1 .6.6Z" stroke-linecap="round" stroke-linejoin="round"/><path d="m3 16 7-3 11 5M16 10a2 2 0 1 1 0-4 2 2 0 0 1 0 4Z" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    const xIcon = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M6.758 17.243 12.001 12m5.243-5.243L12 12m0 0L6.758 6.757M12.001 12l5.243 5.243" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    const syncInput = () => {
        const dt = new DataTransfer();
        files.forEach((f) => dt.items.add(f));
        input.files = dt.files;
    };

    const render = () => {
        list.innerHTML = '';
        files.forEach((file, i) => {
            const li = document.createElement('li');
            li.className = 'sm-upload-file';

            const view = document.createElement('button');
            view.type = 'button';
            view.className = 'sm-upload-file-view';
            view.setAttribute('aria-label', `Lihat foto ${file.name}`);
            view.innerHTML = `<span class="sm-upload-thumb">${imageIcon}</span><span class="sm-upload-name"></span>`;
            view.querySelector('.sm-upload-name').textContent = file.name;
            view.addEventListener('click', () => openPreview(file, view));

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'sm-upload-remove';
            remove.setAttribute('aria-label', `Hapus foto ${file.name}`);
            remove.innerHTML = xIcon;
            remove.addEventListener('click', () => {
                files.splice(i, 1);
                syncInput();
                render();
            });

            li.append(view, remove);
            list.appendChild(li);
        });
    };

    input.addEventListener('change', () => {
        const rejected = [];
        [...input.files].forEach((f) => {
            if (!/^image\/(jpeg|png)$/.test(f.type) || f.size > MAX) rejected.push(f.name);
            else if (!files.some((x) => x.name === f.name && x.size === f.size)) files.push(f);
        });
        errorEl.hidden = rejected.length === 0;
        errorEl.textContent = rejected.length ? `Tidak bisa diunggah (harus JPG/PNG, maks. 5MB): ${rejected.join(', ')}` : '';
        syncInput();
        render();
    });

    // Pop-up
    const modal = (name) => card.querySelector(`[data-modal="${name}"]`);
    const open = (m, from) => {
        opener = from;
        m.hidden = false;
        document.body.style.overflow = 'hidden';
        m.querySelector('.sm-photo-close').focus();
    };
    const close = (m) => {
        m.hidden = true;
        document.body.style.overflow = '';
        const img = m.querySelector('[data-preview-img]');
        if (img && img.src.startsWith('blob:')) URL.revokeObjectURL(img.src);
        opener?.focus();
    };

    const openPreview = (file, from) => {
        const m = modal('preview');
        const img = m.querySelector('[data-preview-img]');
        img.src = URL.createObjectURL(file);
        img.alt = file.name;
        m.querySelector('[data-preview-name]').textContent = file.name;
        open(m, from);
    };

    card.querySelector('[data-upload-example]').addEventListener('click', (e) => open(modal('contoh'), e.currentTarget));
    card.querySelectorAll('[data-modal]').forEach((m) => {
        m.querySelectorAll('[data-modal-close]').forEach((b) => b.addEventListener('click', () => close(m)));
    });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        card.querySelectorAll('[data-modal]:not([hidden])').forEach(close);
    });
});
