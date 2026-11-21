<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rekap Bulanan | Capaian.id - KPI Wonosari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@600&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/rekap.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}" />
</head>

@php
    // Query dasar agar pilihan bulan / bagian / karyawan tetap terbawa saat berpindah.
    $base = ['mode' => $mode, 'bulan' => $month->format('Y-m')];
    if ($mode === 'bagian') {
        $base['bagian'] = $bagian;
    }
    $statusLabel = ['disetujui' => 'Disetujui', 'menunggu' => 'Menunggu', 'ditolak' => 'Ditolak', 'kosong' => 'Kosong'];
    $fotoLabel = ['sah' => 'Foto: Sah', 'belum' => 'Foto: Belum diperiksa', 'tidak_sah' => 'Foto: Tidak sah'];
@endphp

<body class="rekap-page">
    <div class="app-container rekap-container">
        <!-- Header / Rekap Bulanan -->
        <header class="rekap-header">
            <a href="{{ route('admin.dashboard') }}" class="rekap-back-btn" aria-label="Kembali ke beranda">
                <x-icon name="arrow-left" :size="24" />
            </a>
            <div class="rekap-header-text">
                <h1 class="rekap-title">Rekap Bulanan</h1>
                <p class="rekap-subtitle">{{ $mode === 'karyawan' ? 'Rekap per karyawan' : 'Rekap per bagian' }}</p>
            </div>
        </header>

        <main class="rekap-content">
            <!-- Mode: Per Karyawan / Per Bagian -->
            <nav class="rekap-mode" aria-label="Jenis rekap">
                <a href="{{ route('admin.rekap', array_merge($base, ['mode' => 'karyawan'])) }}"
                    class="rekap-mode-seg {{ $mode === 'karyawan' ? 'is-active' : '' }}" @if ($mode === 'karyawan') aria-current="page" @endif>Per Karyawan</a>
                <a href="{{ route('admin.rekap', array_merge($base, ['mode' => 'bagian', 'bagian' => $bagian])) }}"
                    class="rekap-mode-seg {{ $mode === 'bagian' ? 'is-active' : '' }}" @if ($mode === 'bagian') aria-current="page" @endif>Per Bagian</a>
            </nav>

            <!-- Filter bulan -->
            <div class="rekap-filter">
                <span class="rekap-filter-label" id="label-bulan">Pilih bulan</span>
                <div class="rekap-month-row" role="group" aria-labelledby="label-bulan">
                    @foreach ($months as $m)
                        <a href="{{ route('admin.rekap', array_merge($base, ['bulan' => $m['value']])) }}"
                            class="rekap-month-chip {{ $m['active'] ? 'is-active' : '' }}" @if ($m['active']) aria-current="true" @endif>{{ $m['label'] }}</a>
                    @endforeach
                    <form class="rekap-month-other" method="GET" action="{{ route('admin.rekap') }}">
                        @foreach ($base as $k => $v)
                            @if ($k !== 'bulan')
                                <input type="hidden" name="{{ $k }}" value="{{ $v }}" />
                            @endif
                        @endforeach
                        <x-icon name="calendar" width="14" height="14" />
                        <span>Bulan lain</span>
                        <input type="month" name="bulan" class="rekap-month-input" value="{{ $month->format('Y-m') }}"
                            max="{{ now('Asia/Jakarta')->format('Y-m') }}" aria-label="Pilih bulan lain" onchange="this.form.submit()" />
                    </form>
                </div>
            </div>

            <section class="rekap-card" aria-label="Data belum tersedia">
                <div class="rekap-title-group">
                    <h2 class="rekap-section-title">Data belum tersedia</h2>
                    <p class="rekap-section-sub">Rekap ini akan tampil setelah Backend menyediakan data rekap. Angka contoh tidak ditampilkan agar tidak menyesatkan.</p>
                </div>
            </section>
        </main>

        @include('partials.footer')
    </div>

    <script src="{{ asset('js/rekap.js') }}"></script>
</body>

</html>
