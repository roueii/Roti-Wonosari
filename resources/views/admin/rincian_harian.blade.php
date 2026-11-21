<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rincian Harian | Capaian.id - KPI Wonosari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@600&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/rekap.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}" />
</head>

@php
    $statusLabel = ['disetujui' => 'Disetujui', 'menunggu' => 'Menunggu', 'ditolak' => 'Ditolak', 'kosong' => 'Kosong'];
    $fotoLabel = ['sah' => 'Foto: Sah', 'belum' => 'Foto: Belum diperiksa', 'tidak_sah' => 'Foto: Tidak sah'];
@endphp

<body class="rekap-page">
    <div class="app-container rekap-container">
        <!-- Header / Rincian Harian -->
        <header class="rekap-header">
            <a href="{{ route('admin.rekap', ['mode' => 'bagian', 'bagian' => $bagian, 'bulan' => $date->format('Y-m'), 'tanggal' => $date->format('Y-m-d')]) }}"
                class="rekap-back-btn" aria-label="Kembali ke Rekap Bulanan">
                <x-icon name="arrow-left" :size="24" />
            </a>
            <div class="rekap-header-text">
                <h1 class="rekap-title">Rincian Harian</h1>
                <p class="rekap-subtitle">{{ $date->locale('id')->translatedFormat('j F Y') }}</p>
            </div>
        </header>

        <main class="rekap-content">
            <!-- Pindah tanggal -->
            <div class="rekap-stepper is-large">
                @if ($prevDate)
                    <a class="rekap-step-btn" href="{{ route('admin.rekap.rincian', ['bagian' => $bagian, 'tanggal' => $prevDate->format('Y-m-d')]) }}" aria-label="Tanggal sebelumnya"><x-icon name="nav-arrow-left" width="18" height="18" /></a>
                @else
                    <span class="rekap-step-btn is-disabled" aria-hidden="true"><x-icon name="nav-arrow-left" width="18" height="18" /></span>
                @endif
                <span class="rekap-step-date">
                    <x-icon name="calendar" width="16" height="16" />
                    {{ $date->locale('id')->translatedFormat('l, j F Y') }}
                </span>
                @if ($nextDate)
                    <a class="rekap-step-btn" href="{{ route('admin.rekap.rincian', ['bagian' => $bagian, 'tanggal' => $nextDate->format('Y-m-d')]) }}" aria-label="Tanggal berikutnya"><x-icon name="nav-arrow-right" width="18" height="18" /></a>
                @else
                    <span class="rekap-step-btn is-disabled" aria-hidden="true"><x-icon name="nav-arrow-right" width="18" height="18" /></span>
                @endif
            </div>

            <section class="rekap-card" aria-label="Data belum tersedia">
                <div class="rekap-title-group">
                    <h2 class="rekap-section-title">Data belum tersedia</h2>
                    <p class="rekap-section-sub">Rincian harian ini akan tampil setelah Backend menyediakan data rekap. Angka contoh tidak ditampilkan agar tidak menyesatkan.</p>
                </div>
            </section>

            <aside class="rekap-card rekap-info">
                <div class="rekap-info-icon"><x-icon name="info-empty" width="20" height="20" /></div>
                <div>
                    <strong class="rekap-info-title">Cara membaca halaman ini</strong>
                    <p class="rekap-info-text">Penanda Tercapai muncul kalau angka minimum sudah diisi pada hal tersebut. Karyawan yang tidak mengisi selalu tampil sebagai Kosong, tidak pernah dihitung sebagai 0.</p>
                </div>
            </aside>
        </main>

        @include('partials.footer')
    </div>

    <script src="{{ asset('js/rekap.js') }}"></script>
</body>

</html>
