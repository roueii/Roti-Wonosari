<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Approval Tim | Capaian.id - Store Manager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/storemanager.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}" />
</head>

<body class="sm-approval-page">
    <!-- Header (Figma 1068:1882) -->
    <header class="sm-ap-header">
        <a href="{{ route('storemanager.dashboard', ['tab' => 'tim']) }}" class="sm-ap-back" aria-label="Kembali ke Dashboard">
            <x-icon name="arrow-left" width="24" height="24" />
        </a>
        <div class="sm-ap-titles">
            <h1>Approval Tim</h1>
            <p>Capaian harian Tim</p>
        </div>
    </header>

    <main class="app-container sm-ap-main">
        @if (session('status'))
            <div class="sm-ap-flash is-success" role="status"><x-icon name="check" width="18" height="18" /><span>{{ session('status') }}</span></div>
        @endif
        @if (session('error'))
            <div class="sm-ap-flash is-error" role="alert">{{ session('error') }}</div>
        @endif

        <!-- Filter kategori (Figma 1068:1657) -->
        <nav class="sm-ap-filters" aria-label="Filter kategori persetujuan">
            @foreach (['semua' => 'Semua (' . count($approvalItems) . ')', 'frontliner' => 'Frontliner', 'produksi' => 'Produksi', 'driver' => 'Driver', 'sales' => 'Sales/Marketing', 'supervisor' => 'Supervisor'] as $key => $label)
                <button type="button" class="sm-ap-chip {{ $key === 'semua' ? 'is-active' : '' }}" data-filter="{{ $key }}" aria-pressed="{{ $key === 'semua' ? 'true' : 'false' }}">{{ $label }}</button>
            @endforeach
        </nav>

        <!-- Daftar pengajuan (Figma 1068:1668) -->
        <div class="sm-ap-list">
            @if (count($approvalItems) === 0)
                <div class="sm-ap-empty">
                    <strong>Tidak ada laporan yang menunggu persetujuan</strong>
                    <span>Laporan karyawan yang sudah dikirim akan muncul di sini.</span>
                </div>
            @endif
            <p class="sm-ap-empty" id="emptyFilter" hidden>Tidak ada pengajuan di kategori ini.</p>

            @foreach ($approvalItems as $item)
                @php $open = !empty($item['is_expanded']); @endphp
                <article class="sm-ap-card {{ $open ? 'is-open' : '' }}" data-category="{{ $item['role_category'] ?? 'frontliner' }}" id="approvalCard-{{ $item['id'] }}">
                    <form action="{{ route('storemanager.approval.reject', ['id' => $item['id']]) }}" method="POST" class="sm-ap-form" data-require-note="{{ \App\Services\BackendApiService::connected() ? '1' : '0' }}">
                        @csrf
                        <button type="button" class="sm-ap-card-head" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="body-{{ $item['id'] }}" data-toggle-card>
                            <span class="sm-ap-name-row">
                                <span class="sm-ap-name">{{ $item['name'] }}</span>
                                <span class="sm-ap-role">{{ $item['role'] }}</span>
                            </span>
                            <x-icon name="nav-arrow-down" width="16" height="16" class="sm-ap-chevron" aria-hidden="true" />
                        </button>

                        <div class="sm-ap-meta">
                            <span class="sm-ap-time"><x-icon name="clock" width="14" height="14" aria-hidden="true" />{{ $item['submission_meta'] }}</span>
                            <span class="sm-ap-status">{{ $item['status_label'] }}</span>
                        </div>

                        <div class="sm-ap-body" id="body-{{ $item['id'] }}" @unless ($open) hidden @endunless>
                            @foreach ($item['photos'] ?? [] as $photo)
                                <button type="button" class="sm-ap-photo" data-photo-url="{{ $photo['url'] ?? '' }}" data-photo-name="{{ $photo['title'] }}">
                                    <span class="sm-ap-photo-thumb"><x-icon name="media-image" width="24" height="24" aria-hidden="true" /></span>
                                    <span class="sm-ap-photo-text">
                                        <strong>{{ $photo['title'] }}</strong>
                                        <small>{{ $photo['time'] }}</small>
                                        <em>Ketuk untuk lihat foto</em>
                                    </span>
                                </button>
                            @endforeach

                            <div class="sm-ap-checklist">
                                @foreach ($item['categories'] ?? [] as $cat)
                                    <div class="sm-ap-cat-head">
                                        <span>{{ $cat['name'] }}</span>
                                        <small>{{ $cat['ratio'] }}</small>
                                    </div>
                                    <ul class="sm-ap-items">
                                        @foreach ($cat['items'] as $chk)
                                            <li class="sm-ap-item">
                                                <span class="sm-ap-check {{ $chk['checked'] ? 'is-checked' : '' }}" aria-hidden="true">
                                                    @if ($chk['checked'])<x-icon name="check" width="20" height="20" />@endif
                                                </span>
                                                <span>{{ $chk['label'] }}<span class="ds-visually-hidden"> — {{ $chk['checked'] ? 'terpenuhi' : 'tidak terpenuhi' }}</span></span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endforeach

                                <label class="sm-ap-note" for="catatan-{{ $item['id'] }}">
                                    <x-icon name="page-edit" width="14" height="14" aria-hidden="true" />
                                    <span class="sm-ap-note-text">
                                        <strong>Catatan dari Store Manager</strong>
                                        <textarea id="catatan-{{ $item['id'] }}" name="catatan" rows="2" placeholder="Isi catatan untuk dikirim ke Admin"></textarea>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="sm-ap-actions">
                            <button type="submit" class="sm-ap-btn-reject" data-reject>
                                <x-icon name="cancel" width="20" height="20" aria-hidden="true" />
                                <span>Tolak (Revisi)</span>
                            </button>
                            <button type="submit" formaction="{{ route('storemanager.approval.approve', ['id' => $item['id']]) }}" class="sm-ap-btn-approve">
                                <x-icon name="check" width="20" height="20" aria-hidden="true" />
                                <span>Setujui Capaian</span>
                            </button>
                        </div>
                    </form>
                </article>
            @endforeach
        </div>

        @if (count($approvalItems) > 0)
            <!-- Persetujuan sekaligus (Figma 1068:1870) -->
            <section class="sm-ap-bulk" aria-labelledby="bulkTitle">
                <h2 id="bulkTitle">Persetujuan Capaian Sekaligus</h2>
                <p>Seluruh anggota tim di atas telah memenuhi verifikasi SOP minimum hari ini.</p>
                <form action="{{ route('storemanager.approval.approve_all') }}" method="POST">
                    @csrf
                    <button type="submit" class="sm-ap-btn-bulk">
                        <x-icon name="double-check" width="20" height="20" aria-hidden="true" />
                        <span>Setujui Semua Capaian Sekaligus</span>
                    </button>
                </form>
            </section>
        @endif
    </main>

    <!-- Pratinjau foto bukti -->
    <div class="sm-photo-modal" id="photoModal" hidden>
        <div class="sm-photo-backdrop" data-close-photo></div>
        <div class="sm-photo-sheet" role="dialog" aria-modal="true" aria-labelledby="photoModalTitle">
            <div class="sm-photo-sheet-head">
                <h3 id="photoModalTitle">Foto</h3>
                <button type="button" class="sm-photo-close" data-close-photo aria-label="Tutup"><x-icon name="cancel" width="20" height="20" /></button>
            </div>
            <img class="sm-photo-img" id="photoModalImg" alt="" hidden />
            <p class="sm-ap-photo-empty" id="photoModalEmpty" hidden>Pratinjau foto belum tersedia untuk data contoh.</p>
        </div>
    </div>

    <script>
        // Buka/tutup rincian kartu
        const setCard = (card, open) => {
            card.classList.toggle('is-open', open);
            card.querySelector('[data-toggle-card]').setAttribute('aria-expanded', String(open));
            card.querySelector('.sm-ap-body').hidden = !open;
        };
        document.querySelectorAll('[data-toggle-card]').forEach(btn => {
            btn.addEventListener('click', () => {
                const card = btn.closest('.sm-ap-card');
                setCard(card, !card.classList.contains('is-open'));
            });
        });

        // Tolak wajib menyertakan catatan (saat terhubung Backend): buka rincian & fokus ke catatan
        document.querySelectorAll('[data-reject]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const form = btn.closest('form');
                const note = form.querySelector('textarea[name="catatan"]');
                if (form.dataset.requireNote === '1' && !note.value.trim()) {
                    e.preventDefault();
                    setCard(btn.closest('.sm-ap-card'), true);
                    note.placeholder = 'Tulis alasan penolakan sebelum menekan Tolak';
                    note.focus();
                }
            });
        });

        // Filter kategori
        const chips = document.querySelectorAll('.sm-ap-chip');
        chips.forEach(chip => {
            chip.addEventListener('click', () => {
                chips.forEach(c => { c.classList.remove('is-active'); c.setAttribute('aria-pressed', 'false'); });
                chip.classList.add('is-active');
                chip.setAttribute('aria-pressed', 'true');
                const filter = chip.dataset.filter;
                let shown = 0;
                document.querySelectorAll('.sm-ap-card').forEach(card => {
                    const show = filter === 'semua' || card.dataset.category === filter;
                    card.hidden = !show;
                    if (show) shown++;
                });
                document.getElementById('emptyFilter').hidden = shown > 0 || !document.querySelector('.sm-ap-card');
            });
        });

        // Pratinjau foto
        const photoModal = document.getElementById('photoModal');
        const photoImg = document.getElementById('photoModalImg');
        const photoEmpty = document.getElementById('photoModalEmpty');
        let photoOpener = null;
        const closePhoto = () => {
            photoModal.hidden = true;
            document.body.style.overflow = '';
            photoOpener?.focus();
        };
        document.querySelectorAll('.sm-ap-photo').forEach(btn => {
            btn.addEventListener('click', () => {
                photoOpener = btn;
                document.getElementById('photoModalTitle').textContent = btn.dataset.photoName;
                const url = btn.dataset.photoUrl;
                photoImg.hidden = !url;
                photoEmpty.hidden = !!url;
                if (url) {
                    photoImg.src = url;
                    photoImg.alt = btn.dataset.photoName;
                }
                photoModal.hidden = false;
                document.body.style.overflow = 'hidden';
                photoModal.querySelector('.sm-photo-close').focus();
            });
        });
        photoModal.querySelectorAll('[data-close-photo]').forEach(b => b.addEventListener('click', closePhoto));
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && !photoModal.hidden) closePhoto();
        });
    </script>
</body>

</html>
