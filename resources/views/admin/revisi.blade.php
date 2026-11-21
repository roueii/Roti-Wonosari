<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tinjau Revisi | Capaian.id - KPI Wonosari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@600&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}" />
</head>

<body>
    <div class="app-container">
        <!-- 1. Header Halaman Tinjau Revisi -->
        <header class="revisi-header-bar">
            <div class="header-title-wrapper">
                <a href="{{ route('admin.dashboard') }}" class="back-btn" aria-label="Kembali ke beranda">
                    <x-icon name="arrow-left" width="20" height="20" />
                </a>
                <div class="header-titles">
                    <h1 class="header-page-title">Tinjau Revisi</h1>
                    <span class="header-page-subtitle">Laporan yang ditolak dan menunggu perbaikan</span>
                </div>
            </div>

            <button class="menu-btn" id="menuToggle" aria-label="Buka menu navigasi" aria-expanded="false">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </header>

        @if (session('status'))
            <div class="revisi-alert-banner" role="status"><strong class="alert-text">{{ session('status') }}</strong></div>
        @endif

        <!-- 2. Banner Perhatian -->
        <div class="revisi-alert-banner">
            <div class="alert-icon-square" aria-hidden="true">
                <x-icon name="warning-circle" />
            </div>
            <strong class="alert-text">{{ $filterCounts['all'] }} Laporan Perlu Revisi</strong>
        </div>

        <!-- 3. Filter Kategori Tabs -->
        <div class="filter-tabs-row" role="tablist">
            <button class="filter-chip is-active" data-filter="all">Semua ({{ $filterCounts['all'] }})</button>
            <button class="filter-chip" data-filter="sm">Diminta SM ({{ $filterCounts['sm'] }})</button>
            <button class="filter-chip" data-filter="owner">Diminta Owner ({{ $filterCounts['owner'] }})</button>
            <button class="filter-chip" data-filter="store_manager">Store Manager ({{ $filterCounts['store_manager'] }})</button>
        </div>

        <!-- 4. Daftar Kartu Revisi (GET /api/admin/kpi-submissions?status=REJECTED) -->
        <div class="revisi-cards-list">
            @forelse ($reports as $i => $r)
                <article class="revisi-card" id="card-{{ $i }}" data-category="{{ $r['category'] }}">
                    <div class="revisi-card-header">
                        <div class="user-meta-group">
                            <div class="user-name-role">
                                <h2 class="user-name">{{ $r['name'] }}</h2>
                                <span class="user-role-badge">{{ $r['division'] }}</span>
                            </div>
                        </div>
                        <div class="req-origin-badge">
                            <x-icon name="clock" width="13" height="13" />
                            <span>{{ $r['origin'] }}</span>
                        </div>
                    </div>

                    <div class="overdue-bar {{ $r['overdue'] ? 'danger-mode' : 'safe-mode' }}">
                        <div class="overdue-left">
                            <x-icon name="clock" width="14" height="14" />
                            <span>{{ $r['days'] > 0 ? 'Menggantung ' . $r['days'] . ' hari' : 'Ditolak hari ini' }}{{ $r['overdue'] ? ' (Overdue > 48 Jam)' : '' }}</span>
                        </div>
                        @unless ($r['overdue'])
                            <span class="sla-badge">SLA Aman</span>
                        @endunless
                    </div>

                    <div class="revisi-reason-box">
                        <strong class="reason-title">Laporan {{ $r['date_label'] }}</strong>
                        <p class="reason-desc">{{ $r['notes'] }}</p>
                        <span class="reason-date">{{ count($r['indicators']) }} indikator ditolak</span>
                    </div>

                    <div class="revisi-detail-content" id="detail-{{ $i }}" @if ($i > 0) style="display: none;" @endif>
                        <div class="indicators-section-box">
                            <span class="indicators-box-title">STATUS CHECKLIST HARIAN</span>
                            @foreach ($r['indicators'] as $ind)
                                <div class="indicator-item-row">
                                    <div class="indicator-name-group">
                                        <span class="indicator-icon-status {{ $ind['ok'] ? 'success' : 'warning' }}">
                                            <x-icon name="{{ $ind['ok'] ? 'check' : 'warning-circle' }}" />
                                        </span>
                                        <span class="indicator-label">{{ $ind['name'] }}</span>
                                    </div>
                                    <span class="indicator-score {{ $ind['ok'] ? 'dark' : 'warning' }}">{{ $ind['ok'] ? 'Ya' : 'Tidak' }}{{ $ind['evidences'] ? ' · ' . $ind['evidences'] . ' foto' : '' }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="reviewer-quote-box">
                            <div class="quote-icon" aria-hidden="true">
                                <x-icon name="page" />
                            </div>
                            <div class="quote-text-group">
                                <span class="quote-author">{{ $r['reviewer'] }}{{ $r['reviewed_label'] ? ' (' . $r['reviewed_label'] . ')' : '' }}</span>
                                <blockquote class="quote-body">&ldquo;{{ $r['notes'] }}&rdquo;</blockquote>
                            </div>
                        </div>
                    </div>

                    <div class="revisi-card-actions">
                        <button type="button" class="toggle-detail-btn" id="btn-toggle-{{ $i }}" onclick="toggleCardDetail('{{ $i }}')">
                            <span class="toggle-icon ds-caret {{ $i === 0 ? 'is-open' : '' }}"><x-icon name="nav-arrow-down" width="14" height="14" aria-hidden="true" /></span>
                            <span class="toggle-text">{{ $i === 0 ? 'Sembunyikan Rincian' : 'Lihat Rincian Lengkap' }}</span>
                        </button>

                        <form method="POST" action="{{ route('admin.revisi.tindak_lanjut') }}">
                            @csrf
                            <input type="hidden" name="key" value="{{ $r['key'] }}">
                            <button type="submit" class="btn-manual-resolve-green">
                                <x-icon name="check-circle" width="18" height="18" />
                                <span>Tandai sudah ditegur/ditindaklanjuti manual</span>
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="reason-desc" style="text-align:center;padding:24px 0;">Tidak ada laporan yang perlu revisi.</p>
            @endforelse
        </div>


        <!-- Include Side Menu Drawer (Gambar 1) -->
        @include('admin.partials.drawer')
    </div>

    <!-- Toast Notification Element -->
    <div class="toast-msg" id="toastMsg" role="alert" aria-live="polite"></div>

    <script src="{{ asset('js/drawer.js') }}"></script>
    <script>
        const toastEl = document.getElementById('toastMsg');
        let toastTimeout;

        // Toggle Expand/Collapse Rincian Kartu
        function toggleCardDetail(cardKey) {
            const detailEl = document.getElementById(`detail-${cardKey}`);
            const btnEl = document.getElementById(`btn-toggle-${cardKey}`);
            if (!detailEl || !btnEl) return;

            const isHidden = detailEl.style.display === 'none';
            if (isHidden) {
                detailEl.style.display = 'block';
                btnEl.querySelector('.toggle-icon').classList.add('is-open');
                btnEl.querySelector('.toggle-text').textContent = 'Sembunyikan Rincian';
            } else {
                detailEl.style.display = 'none';
                btnEl.querySelector('.toggle-icon').classList.remove('is-open');
                btnEl.querySelector('.toggle-text').textContent = 'Lihat Rincian Lengkap';
            }
        }

        // Filter Tabs
        const filterChips = document.querySelectorAll('.filter-chip');
        const cards = document.querySelectorAll('.revisi-card');

        filterChips.forEach(chip => {
            chip.addEventListener('click', () => {
                filterChips.forEach(c => c.classList.remove('is-active'));
                chip.classList.add('is-active');

                const filter = chip.getAttribute('data-filter');
                cards.forEach(card => {
                    const cardCat = card.getAttribute('data-category');
                    if (filter === 'all' || cardCat === filter) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        function showToast(message) {
            clearTimeout(toastTimeout);
            toastEl.textContent = message;
            toastEl.classList.add('show');
            toastTimeout = setTimeout(() => {
                toastEl.classList.remove('show');
            }, 2600);
        }
    </script>
</body>

</html>
