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
    $fotoLabel = ['sah' => 'Foto: Sah', 'belum' => 'Foto: Belum diperiksa', 'tidak_sah' => 'Foto: Tidak sah', '' => 'Tanpa foto'];
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
                <p class="rekap-subtitle">{{ $rincian['label'] }} &middot; {{ $date->locale('id')->translatedFormat('j F Y') }}</p>
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
                    {{ $rincian['date_label'] }}
                </span>
                @if ($nextDate)
                    <a class="rekap-step-btn" href="{{ route('admin.rekap.rincian', ['bagian' => $bagian, 'tanggal' => $nextDate->format('Y-m-d')]) }}" aria-label="Tanggal berikutnya"><x-icon name="nav-arrow-right" width="18" height="18" /></a>
                @else
                    <span class="rekap-step-btn is-disabled" aria-hidden="true"><x-icon name="nav-arrow-right" width="18" height="18" /></span>
                @endif
            </div>

            <div class="rekap-tiles">
                <div class="rekap-tile is-card"><strong>{{ $rincian['mengisi'] }}</strong><span>Mengisi</span></div>
                <div class="rekap-tile is-card"><strong>{{ $rincian['susulan'] }}</strong><span>Susulan</span></div>
                <div class="rekap-tile is-card"><strong>{{ $rincian['kosong'] }}</strong><span>Kosong</span></div>
            </div>

            <!-- Isian Karyawan -->
            <section class="rekap-card" aria-labelledby="h-isian">
                <div class="rekap-section-header">
                    <div class="rekap-title-group">
                        <h2 class="rekap-section-title" id="h-isian">Isian Karyawan</h2>
                        <p class="rekap-section-sub">{{ count($rincian['cards']) }} karyawan di bagian ini</p>
                    </div>
                    <span class="rekap-chip rekap-chip--brand">{{ $rincian['chip'] }}</span>
                </div>

                <div class="rekap-list" id="isianKaryawan">
                    @foreach ($rincian['cards'] as $i => $card)
                        @php $e = $card['entry']; @endphp
                        @if ($e['status'] === 'kosong')
                            <article class="rekap-emp is-kosong {{ $i >= 5 ? 'is-extra' : '' }}" @if ($i >= 5) hidden @endif>
                                <div class="rekap-emp-head">
                                    <div class="rekap-emp-who">
                                        <span class="rekap-avatar is-small is-muted">{{ $card['emp']['initials'] }}</span>
                                        <strong class="rekap-emp-name is-muted">{{ $card['emp']['name'] }}</strong>
                                    </div>
                                    <span class="rekap-chip rekap-chip--kosong">Kosong</span>
                                </div>
                                <p class="rekap-footnote">{{ $card['note'] }}</p>
                            </article>
                        @else
                            <article class="rekap-emp {{ $i >= 5 ? 'is-extra' : '' }}" @if ($i >= 5) hidden @endif>
                                <div class="rekap-emp-head">
                                    <div class="rekap-emp-who">
                                        <span class="rekap-avatar is-small">{{ $card['emp']['initials'] }}</span>
                                        <div>
                                            <strong class="rekap-emp-name">{{ $card['emp']['name'] }}</strong>
                                            <span class="rekap-foto is-{{ $e['foto'] ?? 'kosong' }} is-small">{{ $fotoLabel[$e['foto'] ?? ''] }}</span>
                                        </div>
                                    </div>
                                    <div class="rekap-pills">
                                        <span class="rekap-chip rekap-chip--{{ $e['status'] }}">{{ $statusLabel[$e['status']] }}</span>
                                        @if ($e['susulan'])
                                            <span class="rekap-chip rekap-chip--brand">Susulan</span>
                                        @endif
                                    </div>
                                </div>
                                <hr class="rekap-divider" />
                                <dl class="rekap-emp-rows">
                                    @foreach ($card['rows'] as $row)
                                        <div>
                                            <dt>{{ $row['name'] }}</dt>
                                            <dd>
                                                @if ($row['tercapai'])
                                                    <span class="rekap-tercapai"><x-icon name="check-circle" width="13" height="13" />Tercapai</span>
                                                @endif
                                                {{ $row['value'] }}
                                            </dd>
                                        </div>
                                    @endforeach
                                </dl>
                                @if ($card['note'])
                                    <p class="rekap-footnote {{ ($card['note_tone'] ?? null) === 'error' ? 'is-error' : '' }}">{{ $card['note'] }}</p>
                                @endif
                            </article>
                        @endif
                    @endforeach
                </div>

                @if (count($rincian['cards']) > 5)
                    <button type="button" class="rekap-link-btn" data-toggle-extra="isianKaryawan"
                        data-more="Lihat {{ count($rincian['cards']) - 5 }} karyawan lain" data-less="Tampilkan lebih sedikit" aria-expanded="false">
                        <span>Lihat {{ count($rincian['cards']) - 5 }} karyawan lain</span>
                        <x-icon name="nav-arrow-right" width="16" height="16" />
                    </button>
                @endif
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
