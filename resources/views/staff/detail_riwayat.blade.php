<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Detail Riwayat | Frontliner Capaian.id</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@600&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/frontliner.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}" />
</head>

<body>
    <div class="app-container">
        <!-- 1. Header Bar Sesuai Desain Figma (Tombol Panah Kembali + Judul Detail Riwayat) -->
        <header class="fl-riwayat-header">
            <a href="{{ route('frontliner.riwayat') }}" class="fl-step-close-btn" aria-label="Kembali ke Daftar Riwayat">
                <x-icon name="arrow-left" />
            </a>

            <div class="fl-riwayat-header-text">
                <h1 class="fl-riwayat-title">Detail Riwayat</h1>
                <p class="fl-riwayat-subtitle">Capaian harian saya</p>
            </div>
        </header>

        @php
            $isRevisi = $reportMeta['is_revisi'] ?? false;
        @endphp

        <!-- 2. Ringkasan Riwayat (Figma 1084:2277 / 1084:2440) -->
        <section class="fl-dr-summary-card" aria-label="Ringkasan status laporan">
            <div class="fl-dr-summary-top">
                <h2 class="fl-dr-summary-title">{{ $reportMeta['title'] ?? 'Riwayat — 24 Okt' }}</h2>
                @if($isRevisi)
                    <span class="fl-history-badge badge-revisi">PERLU REVISI</span>
                @elseif(str_contains(strtoupper($reportMeta['status'] ?? ''), 'MENUNGGU'))
                    <span class="fl-history-badge badge-menunggu">MENUNGGU</span>
                @else
                    <span class="fl-history-badge badge-disetujui">DISETUJUI</span>
                @endif
            </div>
            <div class="fl-dr-summary-time">
                <x-icon name="clock" width="16" height="16" aria-hidden="true" />
                <span>{{ $reportMeta['submitted_at'] ?? 'Dikirim 24 Okt 2026, 21:05 WIB' }}</span>
            </div>
        </section>

        @if($isRevisi)
        <!-- 3. Catatan Revisi -->
        <section class="fl-dr-revision-card" aria-labelledby="heading-catatan-revisi">
            <div class="fl-dr-revision-header">
                <div class="fl-dr-revision-meta">
                    <span class="fl-dr-revision-label" id="heading-catatan-revisi">CATATAN REVISI</span>
                    <span class="fl-dr-revision-author">Diminta oleh: <strong>{{ $revisionNote['requester'] ?? 'User (Store Manager)' }}</strong></span>
                </div>
                <span class="fl-dr-revision-time-chip">{{ $revisionNote['time'] ?? '25 Okt, 08:30 WIB' }}</span>
            </div>
            <div class="fl-dr-revision-bubble">{{ $revisionNote['text'] ?? '"Foto bukti Penjualan kurang jelas, mohon upload ulang."' }}</div>
            <a href="{{ $revisionNote['cta_url'] ?? route('frontliner.penjualan') }}" class="fl-dr-btn-repair">
                <span>{{ $revisionNote['cta_title'] ?? 'PERBAIKI CAPAIAN' }}</span>
                <x-icon name="arrow-right" width="18" height="18" aria-hidden="true" />
            </a>
        </section>
        @endif

        <!-- 4. Ringkasan Laporan Harian -->
        <section aria-labelledby="heading-ringkasan-laporan">
            <div class="fl-dr-section-header">
                <h3 class="fl-dr-section-title" id="heading-ringkasan-laporan">RINGKASAN LAPORAN HARIAN</h3>
                <span class="fl-dr-section-count">{{ count($categories) }} Kategori</span>
            </div>

            <div class="fl-dr-categories-container">
                @foreach($categories as $cat)
                    @php
                        $icon = ['cash' => 'cash', 'headset' => 'headset-help', 'lightbulb' => 'light-bulb', 'calculator' => 'calculator', 'sparkles' => 'sparks'][$cat['icon']] ?? 'shield-check';
                    @endphp
                    <a href="{{ $cat['url'] ?? '#' }}" class="fl-dr-category-item {{ $cat['is_revisi'] ? 'is-highlighted' : '' }}" aria-label="Kategori {{ $cat['name'] }}">
                        <div class="fl-dr-category-left">
                            <div class="produksi-cat-icon-box {{ $cat['is_revisi'] ? 'is-orange' : '' }}">
                                <x-icon :name="$icon" width="22" height="22" />
                            </div>
                            <div class="fl-dr-category-info">
                                <div class="fl-dr-category-title-row">
                                    <span class="fl-dr-category-title">{{ $cat['name'] }}</span>
                                    @if($cat['is_revisi'])
                                        <span class="fl-dr-category-badge-warning">PERLU REVISI</span>
                                    @endif
                                </div>
                                <span class="fl-dr-category-sub">{{ $cat['subtitle'] }}</span>
                            </div>
                        </div>
                        <span class="fl-dr-category-chevron" aria-hidden="true"><x-icon name="nav-arrow-right" width="16" height="16" /></span>
                    </a>
                @endforeach
            </div>
        </section>

        @if($isRevisi)
        <!-- 5. Catatan bawah -->
        <aside class="fl-dr-notice-card" role="note">
            <x-icon name="info-empty" class="fl-dr-notice-icon" width="20" height="20" aria-hidden="true" />
            <p class="fl-dr-notice-text">Catatan: Laporan ini perlu direvisi sesuai catatan Store Manager. Perubahan hanya dibuka untuk kategori yang membutuhkan perbaikan.</p>
        </aside>
        @endif
    </div>
</body>

</html>
