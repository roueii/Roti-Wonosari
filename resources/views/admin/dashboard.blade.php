<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard Admin | Capaian.id - KPI Wonosari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@600&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}" />
</head>

<body>
    <div class="app-container">
        <!-- 1. Header Card (Profil & Menu) -->
        <header class="header-card">
            <div class="profile-group">
                <div class="avatar-circle" aria-hidden="true">
                    <x-icon name="user" />
                </div>
                <div class="profile-info">
                    <h1 class="profile-name">Halo, {{ $user['name'] ?? 'Admin' }}</h1>
                    <span class="profile-role">Admin</span>
                </div>
            </div>

            <button class="menu-btn" id="menuToggle" aria-label="Buka menu navigasi" aria-expanded="false">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </header>

        <!-- 2. Section Card: Perlu Perhatian -->
        <section class="card-section" aria-labelledby="perhatian-heading">
            <div class="section-header">
                <div class="title-group">
                    <h2 id="perhatian-heading">Perlu Perhatian</h2>
                    <p>Ringkasan tindakan hari ini</p>
                </div>
                <span class="badge-pill">Prioritas</span>
            </div>

            <div class="attention-list">
                <!-- Item 1: Menunggu Approval Final -->
                <a href="{{ route('admin.approve') }}" class="attention-item">
                    <div class="attention-left">
                        <div class="icon-box" aria-hidden="true">
                            <x-icon name="verified-user" />
                        </div>
                        <div class="attention-text">
                            <span class="attention-title">Menunggu Approval Final</span>
                            <span class="attention-subtitle">Sudah disetujui Store Manager</span>
                        </div>
                    </div>
                    <div class="attention-right">
                        <span class="count-number">{{ $attention['final'] }}</span>
                        <span class="chevron-arrow" aria-hidden="true"><x-icon name="nav-arrow-right" width="16" height="16" aria-hidden="true" /></span>
                    </div>
                </a>

                <!-- Item 2: Perlu Revisi -->
                <a href="{{ route('admin.revisi') }}" class="attention-item">
                    <div class="attention-left">
                        <div class="icon-box" aria-hidden="true">
                            <x-icon name="page" />
                        </div>
                        <div class="attention-text">
                            <span class="attention-title">Perlu Revisi</span>
                            <span class="attention-subtitle">Laporan ditolak, menunggu perbaikan</span>
                        </div>
                    </div>
                    <div class="attention-right">
                        <span class="count-number">{{ $attention['revisi'] }}</span>
                        <span class="chevron-arrow" aria-hidden="true"><x-icon name="nav-arrow-right" width="16" height="16" aria-hidden="true" /></span>
                    </div>
                </a>
            </div>
        </section>

        <!-- 3. Section Card: Ringkasan Divisi -->
        <section class="card-section" aria-labelledby="divisi-heading">
            <div class="section-header">
                <div class="title-group">
                    <h2 id="divisi-heading">Ringkasan Divisi</h2>
                    <p>{{ count($divisions) }} divisi &middot; {{ array_sum(array_column($divisions, 'staff_count')) }} karyawan</p>
                </div>
            </div>

            <div class="division-list">
                @forelse ($divisions as $div)
                    @php $pct = $div['total'] > 0 ? round($div['done'] / $div['total'] * 100) : 0; @endphp
                    <div class="division-item">
                        <div class="division-info-row">
                            <span class="division-label">{{ $div['name'] }} ({{ $div['staff_count'] }} staf)</span>
                            <span class="division-percentage">{{ $div['done'] }}/{{ $div['total'] }}</span>
                        </div>
                        <div class="progress-track" role="progressbar" aria-label="{{ $div['name'] }}" aria-valuenow="{{ $div['done'] }}" aria-valuemin="0" aria-valuemax="{{ $div['total'] }}">
                            <div class="progress-fill" style="width: {{ $pct }}%;"></div>
                        </div>
                    </div>
                @empty
                    <p class="division-label">Data belum tersedia.</p>
                @endforelse
            </div>

            <!-- Legend Info -->
            <div class="division-legend">
                <div class="legend-item">
                    <span class="legend-dot tercapai"></span>
                    <span>Tercapai</span>
                </div>
                <div class="legend-item">
                    <span class="legend-dot proses"></span>
                    <span>Proses</span>
                </div>
            </div>

            <a href="{{ route('admin.rekap') }}" class="btn-rekap-bulanan">Lihat Detail Rekap Bulanan</a>
        </section>

        <!-- 4. Section: Menu Admin -->
        <section class="admin-menu-section" aria-labelledby="menu-admin-heading">
            <div class="admin-menu-header">
                <h2 id="menu-admin-heading">Menu Admin</h2>
                <span class="shortcut-label">Pintasan Cepat</span>
            </div>

            <div class="menu-grid">
                <!-- Card 1: Tinjau Revisi -->
                <a href="{{ route('admin.revisi') }}" class="menu-card">
                    <div class="menu-card-top">
                        <div class="menu-icon" aria-hidden="true">
                            <x-icon name="page" />
                        </div>
                        <span class="menu-badge pill">{{ $menuBadges['revisi'] }} aktif</span>
                    </div>
                    <div class="menu-card-bottom">
                        <span class="menu-title">Tinjau Revisi</span>
                        <span class="menu-chevron" aria-hidden="true"><x-icon name="nav-arrow-right" width="16" height="16" aria-hidden="true" /></span>
                    </div>
                </a>

                <!-- Card 2: Kelola Akun -->
                <a href="{{ route('admin.accounts') }}" class="menu-card">
                    <div class="menu-card-top">
                        <div class="menu-icon" aria-hidden="true">
                            <x-icon name="group" />
                        </div>
                        <span class="menu-badge text-only">{{ $menuBadges['staff'] }} staf</span>
                    </div>
                    <div class="menu-card-bottom">
                        <span class="menu-title">Kelola Akun</span>
                        <span class="menu-chevron" aria-hidden="true"><x-icon name="nav-arrow-right" width="16" height="16" aria-hidden="true" /></span>
                    </div>
                </a>

                <!-- Card 3: Kelola KPI -->
                <a href="{{ route('admin.kpi') }}" class="menu-card">
                    <div class="menu-card-top">
                        <div class="menu-icon" aria-hidden="true">
                            <x-icon name="list" />
                        </div>
                        <span class="menu-badge text-only">{{ $menuBadges['divisi'] }} divisi</span>
                    </div>
                    <div class="menu-card-bottom">
                        <span class="menu-title">Kelola KPI</span>
                        <span class="menu-chevron" aria-hidden="true"><x-icon name="nav-arrow-right" width="16" height="16" aria-hidden="true" /></span>
                    </div>
                </a>

                <!-- Card 4: Approve Laporan -->
                <a href="{{ route('admin.approve') }}" class="menu-card">
                    <div class="menu-card-top">
                        <div class="menu-icon" aria-hidden="true">
                            <x-icon name="verified-user" />
                        </div>
                        <span class="menu-badge pill">{{ $menuBadges['final'] }} antrean</span>
                    </div>
                    <div class="menu-card-bottom">
                        <span class="menu-title">Approve Laporan</span>
                        <span class="menu-chevron" aria-hidden="true"><x-icon name="nav-arrow-right" width="16" height="16" aria-hidden="true" /></span>
                    </div>
                </a>
            </div>
        </section>

        @include('partials.footer')

        <!-- Include Side Menu Drawer (Gambar 1) -->
        @include('admin.partials.drawer')
    </div>

    <!-- Toast Notification Element -->
    <div class="toast-msg" id="toastMsg" role="alert" aria-live="polite"></div>

    <script src="{{ asset('js/drawer.js') }}"></script>
    <script>
        const toastEl = document.getElementById('toastMsg');
        let toastTimeout;

        // Toast feedback for button clicks
        function showToast(message) {
            clearTimeout(toastTimeout);
            toastEl.textContent = message;
            toastEl.classList.add('show');
            toastTimeout = setTimeout(() => {
                toastEl.classList.remove('show');
            }, 2400);
        }
    </script>
</body>

</html>
