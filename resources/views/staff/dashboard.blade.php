<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard Frontliner | Capaian.id - KPI Wonosari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@600&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/frontliner.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
</head>

<body>
    <div class="app-container">
        <!-- 1. Header Card (Profil & Menu) Sesuai Figma Revisi -->
        <header class="fl-header">
            <a href="{{ route('frontliner.profile') }}" class="fl-profile-group" style="text-decoration: none; color: inherit;" aria-label="Lihat Profil Pengguna">
                <div class="fl-avatar-circle" aria-hidden="true">
                    <x-icon name="user" />
                </div>
                <div class="fl-profile-info">
                    <h1 class="fl-profile-name">Halo, {{ $user['name'] ?? 'User' }}</h1>
                    <span class="fl-profile-role">Frontliner</span>
                </div>
            </a>

            <button class="menu-btn" id="menuToggle" aria-label="Buka menu navigasi" aria-expanded="false">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </header>

        <!-- 2. Ringkasan Hari Ini (Figma 1092:2071) -->
        <section class="fl-summary-card" aria-labelledby="ringkasan-heading">
            <div class="fl-summary-header">
                <div class="fl-title-group">
                    <h2 id="ringkasan-heading">Ringkasan Hari Ini</h2>
                    <p>Indikator Capaian Harian</p>
                </div>
                <span class="fl-date-pill">{{ $todayBadge ?? 'Hari ini, 30 Sep' }}</span>
            </div>

            <div class="fl-revised-donut-card">
                <div class="fl-revised-chart-left" aria-label="{{ $metricsSummary['label'] ?? '' }} indikator {{ $metricsSummary['status_text'] ?? 'Tuntas' }}">
                    <svg class="fl-revised-donut-svg" viewBox="0 0 100 100" aria-hidden="true">
                        <circle class="fl-revised-donut-track" cx="50" cy="50" r="40"></circle>
                        <circle class="fl-revised-donut-progress" cx="50" cy="50" r="40"
                            stroke-dasharray="251.32"
                            stroke-dashoffset="{{ 251.32 - (251.32 * ($metricsSummary['completed'] / max($metricsSummary['total'], 1))) }}"></circle>
                    </svg>
                    <div class="fl-revised-donut-center">
                        <span class="fl-revised-donut-val">{{ $metricsSummary['label'] ?? '5/22' }}</span>
                        <span class="fl-revised-donut-sub">{{ $metricsSummary['status_text'] ?? 'Tuntas' }}</span>
                    </div>
                </div>

                <div class="fl-revised-progress-list">
                    @foreach($progressRows as $row)
                    <div class="fl-revised-progress-item">
                        <div class="fl-revised-progress-header">
                            <strong>{{ $row['title'] }}</strong>
                            <span>{{ $row['current'] }}/{{ $row['target'] }}</span>
                        </div>
                        <div class="fl-revised-bar-track">
                            <div class="fl-revised-bar-fill" style="width: {{ $row['percent'] }}%;"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="fl-legend-bar-revised">
                <div class="fl-legend-note-item"><span class="fl-dot green"></span><span>Hijau: Tercapai</span></div>
                <div class="fl-legend-note-item"><span class="fl-dot amber"></span><span>Kuning: Belum diisi</span></div>
                <div class="fl-legend-note-item"><span class="fl-dot red"></span><span>Merah: Tidak tercapai</span></div>
            </div>
        </section>

        <!-- 3. Tugas Harian -->
        <section class="fl-section-group" aria-labelledby="tugas-heading">
            <div class="fl-section-header">
                <h2 id="tugas-heading">Tugas Harian</h2>
                <p>Wajib diisi hari ini</p>
            </div>

            <a href="{{ $dailyTask['start_route'] }}" class="fl-task-banner" aria-label="Mulai mengisi capaian hari ini">
                <div class="fl-task-banner-content">
                    <span class="fl-task-banner-title">{{ $dailyTask['banner_title'] }}</span>
                    <div class="fl-task-banner-meta">
                        <x-icon name="clock" class="fl-task-clock-icon" aria-hidden="true" />
                        <span>{{ $dailyTask['banner_sub'] }}</span>
                    </div>
                </div>
                <div class="fl-task-arrow-btn" aria-hidden="true">
                    <x-icon name="arrow-right" />
                </div>
            </a>

            <div class="fl-preview-box">
                <div class="fl-preview-top">
                    <span class="fl-preview-title">Preview Status</span>
                    <span class="fl-preview-autosave">Tersimpan otomatis</span>
                </div>

                <div class="fl-preview-grid">
                    @foreach($dailyTask['preview_items'] as $item)
                    <a href="{{ $item['route'] }}" class="fl-preview-card">
                        <span class="fl-item-title">{{ $item['title'] }}</span>
                        <span class="fl-badge-pill {{ $item['badge_type'] }}">
                            <span class="fl-dot {{ ['success' => 'green', 'warning' => 'amber'][$item['badge_type']] ?? 'red' }}"></span>
                            <span>{{ $item['status_label'] }}</span>
                        </span>
                    </a>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- 4. Capaian Terbaru -->
        <section class="fl-section-group" aria-labelledby="terbaru-heading">
            <div class="fl-section-header">
                <h2 id="terbaru-heading">Capaian Terbaru</h2>
            </div>

            <div class="fl-recent-list">
                @foreach($recentAchievements as $history)
                <a href="{{ $history['route'] }}" class="fl-recent-card">
                    <div class="fl-recent-left">
                        <span class="fl-recent-title">{{ $history['title'] }}</span>
                        <span class="fl-recent-date">{{ $history['sub'] }}</span>
                    </div>
                    <span class="fl-status-badge {{ $history['badge_class'] }}">{{ $history['status_label'] }}</span>
                </a>
                @endforeach
            </div>
        </section>

        <!-- Side Navigation Drawer Partial Khusus Frontliner -->
        @include('staff.partials.drawer')
    </div>

    <!-- Script Drawer Menu Navigasi -->
    <script src="{{ asset('js/drawer.js') }}"></script>
</body>

</html>
