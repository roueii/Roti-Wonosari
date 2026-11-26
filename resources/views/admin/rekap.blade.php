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
    if ($mode === 'karyawan') {
        $base['karyawan'] = $employee['id'] ?? null;
    } else {
        $base['bagian'] = $bagian;
    }
    $statusLabel = ['disetujui' => 'Disetujui', 'menunggu' => 'Menunggu', 'ditolak' => 'Ditolak', 'kosong' => 'Kosong'];
    $fotoLabel = ['sah' => 'Foto: Sah', 'belum' => 'Foto: Belum diperiksa', 'tidak_sah' => 'Foto: Tidak sah', '' => 'Tanpa foto'];
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
                <a href="{{ route('admin.rekap', array_merge($base, ['mode' => 'karyawan', 'karyawan' => $employee['id'] ?? null])) }}"
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

            @if ($unavailable)
                <section class="rekap-card" role="alert">
                    <div class="rekap-title-group">
                        <h2 class="rekap-section-title">Data belum tersedia</h2>
                        <p class="rekap-section-sub">Server sedang tidak dapat dihubungi. Coba muat ulang beberapa saat lagi.</p>
                    </div>
                </section>
            @elseif ($mode === 'karyawan' && ! $employee)
                <section class="rekap-card" role="status">
                    <div class="rekap-title-group">
                        <h2 class="rekap-section-title">Belum ada karyawan</h2>
                        <p class="rekap-section-sub">Tambahkan akun karyawan di menu Kelola Akun untuk melihat rekapnya.</p>
                    </div>
                </section>
            @elseif ($mode === 'karyawan')
                <!-- Pilih karyawan -->
                <form class="rekap-card rekap-pick" method="GET" action="{{ route('admin.rekap') }}">
                    <input type="hidden" name="mode" value="karyawan" />
                    <input type="hidden" name="bulan" value="{{ $month->format('Y-m') }}" />
                    <div class="rekap-pick-text">
                        <span class="rekap-pick-label">Karyawan</span>
                        <strong class="rekap-pick-name">{{ $employee['name'] }}</strong>
                        <span class="rekap-pick-meta">{{ count($employeeOptions) }} karyawan · ketuk untuk ganti</span>
                    </div>
                    <x-icon name="nav-arrow-right" width="20" height="20" />
                    <select name="karyawan" class="rekap-pick-select" aria-label="Pilih karyawan" onchange="this.form.submit()">
                        @foreach ($employeeOptions as $opt)
                            <option value="{{ $opt['id'] }}" @selected($opt['id'] === $employee['id'])>{{ $opt['name'] }} · {{ $opt['bagian_label'] }}{{ $opt['inactive'] ? ' (nonaktif)' : '' }}</option>
                        @endforeach
                    </select>
                </form>
                <!-- Identitas karyawan -->
                <section class="rekap-card" aria-label="Identitas karyawan">
                    <div class="rekap-identity">
                        <div class="rekap-avatar">{{ $employee['initials'] }}</div>
                        <div class="rekap-identity-text">
                            <strong class="rekap-identity-name">{{ $employee['name'] }}</strong>
                            <span class="rekap-identity-meta">{{ $employee['bagian_label'] }} &middot; ID {{ $employee['code'] }}</span>
                        </div>
                        @if ($employee['inactive_since'])
                            <span class="rekap-chip rekap-chip--inactive">Nonaktif</span>
                        @endif
                    </div>
                    @if ($employee['inactive_since'])
                        <p class="rekap-note-box">Akun ini sudah dinonaktifkan. Data periode lama tetap ditampilkan.</p>
                    @endif
                </section>

                <!-- Ringkasan Hari -->
                <section class="rekap-card" aria-labelledby="h-ringkasan">
                    <div class="rekap-title-group">
                        <h2 class="rekap-section-title" id="h-ringkasan">Ringkasan Hari</h2>
                        <p class="rekap-section-sub">{{ $rekap['period'] }}</p>
                    </div>
                    <div class="rekap-tiles">
                        <div class="rekap-tile"><strong>{{ $rekap['filled'] }}</strong><span>Hari terisi</span></div>
                        <div class="rekap-tile"><strong>{{ $rekap['susulan'] }}</strong><span>Hari susulan</span></div>
                        <div class="rekap-tile"><strong>{{ $rekap['empty'] }}</strong><span>Hari kosong</span></div>
                    </div>
                    <p class="rekap-footnote">Hari susulan sudah termasuk dalam hari terisi. Dari {{ $rekap['filled'] }} hari terisi, {{ $rekap['counted'] }} hari ikut dihitung.</p>
                </section>

                <!-- Hal yang Dicatat -->
                <section class="rekap-card" aria-labelledby="h-hal">
                    <div class="rekap-section-header">
                        <div class="rekap-title-group">
                            <h2 class="rekap-section-title" id="h-hal">Hal yang Dicatat</h2>
                            <p class="rekap-section-sub">Total dan rata-rata per hari</p>
                        </div>
                        <span class="rekap-chip rekap-chip--brand">{{ count($rekap['metrics']) }} hal</span>
                    </div>
                    <div class="rekap-list">
                        @foreach ($rekap['metrics'] as $metric)
                            <div class="rekap-metric">
                                <div class="rekap-metric-top">
                                    <span class="rekap-metric-name">
                                        {{ $metric['name'] }}
                                        @if ($metric['inactive'])
                                            <span class="rekap-chip rekap-chip--inactive">Nonaktif</span>
                                        @endif
                                    </span>
                                    <span class="rekap-chip rekap-chip--unit">{{ $metric['unit'] }}</span>
                                </div>
                                <dl class="rekap-metric-values">
                                    <div><dt>Total</dt><dd>{{ $metric['total'] }}</dd></div>
                                    <div><dt>Rata-rata</dt><dd>{{ $metric['avg'] }}</dd></div>
                                </dl>
                                @if ($metric['note'])
                                    <p class="rekap-footnote">{{ $metric['note'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>

                <!-- Status Harian -->
                <section class="rekap-card" aria-labelledby="h-status">
                    <div class="rekap-title-group">
                        <h2 class="rekap-section-title" id="h-status">Status Harian</h2>
                        <p class="rekap-section-sub">{{ $rekap['month_label'] }} &middot; tanggal kosong terlihat langsung</p>
                    </div>

                    <div class="rekap-calendar">
                        <div class="rekap-cal-dow" aria-hidden="true">
                            @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $dow)
                                <span>{{ $dow }}</span>
                            @endforeach
                        </div>
                        <ol class="rekap-cal-grid" aria-label="Status harian {{ $rekap['month_label'] }}">
                            @for ($i = 0; $i < $rekap['offset']; $i++)
                                <li class="rekap-day is-blank" aria-hidden="true"></li>
                            @endfor
                            @foreach ($rekap['calendar'] as $c)
                                @php
                                    $stateText = match ($c['state']) {
                                        'disetujui' => 'Disetujui', 'menunggu' => 'Menunggu', 'ditolak' => 'Ditolak',
                                        'kosong' => 'Kosong', 'libur' => 'Hari libur', default => 'Belum berjalan',
                                    };
                                @endphp
                                <li class="rekap-day is-{{ $c['state'] }}">
                                    <span>{{ $c['day'] }}</span>
                                    @if ($c['susulan'])
                                        <span class="rekap-day-s" aria-hidden="true">S</span>
                                    @endif
                                    <span class="ds-visually-hidden">{{ $c['label'] }}: {{ $stateText }}{{ $c['susulan'] ? ', susulan' : '' }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>

                    <div class="rekap-legend">
                        <span><i class="lg lg-disetujui"></i>Disetujui</span>
                        <span><i class="lg lg-menunggu"></i>Menunggu</span>
                        <span><i class="lg lg-ditolak"></i>Ditolak</span>
                        <span><i class="lg lg-kosong"></i>Kosong</span>
                        <span>S = susulan</span>
                    </div>

                    <h3 class="rekap-subheading">Rincian status</h3>
                    <div class="rekap-list" id="rincianStatus">
                        @forelse ($rekap['details'] as $i => $d)
                            <div class="rekap-date-row {{ $i >= 5 ? 'is-extra' : '' }}" @if ($i >= 5) hidden @endif>
                                <div class="rekap-date-box">
                                    <strong>{{ $d['day'] }}</strong>
                                    <span>{{ $d['dow'] }}</span>
                                </div>
                                <div class="rekap-date-text">
                                    <div class="rekap-pills">
                                        <span class="rekap-chip rekap-chip--{{ $d['status'] }}">{{ $statusLabel[$d['status']] }}</span>
                                        @if ($d['susulan'])
                                            <span class="rekap-chip rekap-chip--brand">Susulan</span>
                                        @endif
                                    </div>
                                    @if ($d['status'] === 'kosong')
                                        <span class="rekap-foto is-kosong">Belum ada isian</span>
                                    @else
                                        <span class="rekap-foto is-{{ $d['foto'] ?? 'kosong' }}">{{ $fotoLabel[$d['foto'] ?? ''] }}</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="rekap-footnote">Belum ada tanggal kerja pada bulan ini.</p>
                        @endforelse
                    </div>
                    @if (count($rekap['details']) > 5)
                        <button type="button" class="rekap-link-btn" data-toggle-extra="rincianStatus"
                            data-more="Lihat {{ count($rekap['details']) }} tanggal" data-less="Tampilkan lebih sedikit" aria-expanded="false">
                            <span>Lihat {{ count($rekap['details']) }} tanggal</span>
                            <x-icon name="nav-arrow-right" width="16" height="16" />
                        </button>
                    @endif
                </section>

                <!-- Aturan hitung -->
                <aside class="rekap-card rekap-info">
                    <div class="rekap-info-icon"><x-icon name="info-empty" width="20" height="20" /></div>
                    <div>
                        <strong class="rekap-info-title">Aturan hitung</strong>
                        <p class="rekap-info-text">Hanya isian Disetujui yang fotonya tidak dinyatakan Tidak sah yang ikut dihitung. Isian Menunggu dan Ditolak belum masuk total. Rata-rata dihitung dari jumlah hari yang dihitung.</p>
                    </div>
                </aside>
            @else
                <!-- Pilih bagian -->
                <form class="rekap-card rekap-pick" method="GET" action="{{ route('admin.rekap') }}">
                    <input type="hidden" name="mode" value="bagian" />
                    <input type="hidden" name="bulan" value="{{ $month->format('Y-m') }}" />
                    <div class="rekap-pick-text">
                        <span class="rekap-pick-label">Bagian</span>
                        <strong class="rekap-pick-name">{{ $rekap['label'] }}</strong>
                        <span class="rekap-pick-meta">{{ $rekap['count'] }} karyawan{{ $rekap['inactive'] ? ' · ' . $rekap['inactive'] . ' nonaktif' : '' }}</span>
                    </div>
                    <x-icon name="nav-arrow-right" width="20" height="20" />
                    <select name="bagian" class="rekap-pick-select" aria-label="Pilih bagian" onchange="this.form.submit()">
                        @foreach ($bagianOptions as $opt)
                            <option value="{{ $opt['key'] }}" @selected($opt['key'] === $bagian)>{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </form>

                <!-- Ringkasan Hari -->
                <section class="rekap-card" aria-labelledby="h-ringkasan-bagian">
                    <div class="rekap-title-group">
                        <h2 class="rekap-section-title" id="h-ringkasan-bagian">Ringkasan Hari</h2>
                        <p class="rekap-section-sub">{{ $rekap['period'] }}</p>
                    </div>
                    <div class="rekap-tiles">
                        <div class="rekap-tile"><strong>{{ $rekap['filled'] }}</strong><span>Hari terisi</span></div>
                        <div class="rekap-tile"><strong>{{ $rekap['susulan'] }}</strong><span>Hari susulan</span></div>
                        <div class="rekap-tile"><strong>{{ $rekap['empty'] }}</strong><span>Hari kosong</span></div>
                    </div>
                </section>

                <!-- Rekap per Karyawan -->
                <section class="rekap-card" aria-labelledby="h-tabel">
                    <div class="rekap-title-group">
                        <h2 class="rekap-section-title" id="h-tabel">Rekap per Karyawan</h2>
                        <p class="rekap-section-sub">Satu baris per karyawan, kolom sama</p>
                    </div>
                    <div class="rekap-table-viewport" tabindex="0" role="region" aria-labelledby="h-tabel">
                        <table class="rekap-table" id="tabelKaryawan">
                            <thead>
                                <tr>
                                    <th scope="col">Karyawan</th>
                                    @foreach ($rekap['columns'] as $col)
                                        <th scope="col">{{ $col }}</th>
                                    @endforeach
                                    <th scope="col">Terisi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rekap['rows'] as $i => $row)
                                    <tr class="{{ $i >= 5 ? 'is-extra' : '' }}" @if ($i >= 5) hidden @endif>
                                        <th scope="row">
                                            <a href="{{ route('admin.rekap', ['mode' => 'karyawan', 'karyawan' => $row['emp']['id'], 'bulan' => $month->format('Y-m')]) }}">{{ $row['emp']['name'] }}</a>
                                            @if ($row['emp']['inactive_since'])
                                                <span class="rekap-chip rekap-chip--inactive">Nonaktif</span>
                                            @endif
                                        </th>
                                        @foreach ($row['cells'] as $cell)
                                            <td><strong>{{ $cell['total'] }}</strong><span>{{ $cell['avg'] }}</span></td>
                                        @endforeach
                                        <td><strong>{{ $row['filled'] }}</strong><span>{{ $row['susulan'] }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="rekap-footnote">Geser tabel ke samping untuk melihat kolom lain.</p>
                    @if (count($rekap['rows']) > 5)
                        <button type="button" class="rekap-link-btn" data-toggle-extra="tabelKaryawan"
                            data-more="Lihat {{ count($rekap['rows']) - 5 }} karyawan lain" data-less="Tampilkan lebih sedikit" aria-expanded="false">
                            <span>Lihat {{ count($rekap['rows']) - 5 }} karyawan lain</span>
                            <x-icon name="nav-arrow-right" width="16" height="16" />
                        </button>
                    @endif
                </section>

                <!-- Rincian Harian -->
                <section class="rekap-card" aria-labelledby="h-rincian">
                    <div class="rekap-title-group">
                        <h2 class="rekap-section-title" id="h-rincian">Rincian Harian</h2>
                        <p class="rekap-section-sub">Pilih tanggal untuk melihat isian tiap karyawan</p>
                    </div>
                    <div class="rekap-stepper">
                        @if ($prevDate)
                            <a class="rekap-step-btn" href="{{ route('admin.rekap', array_merge($base, ['tanggal' => $prevDate->format('Y-m-d')])) }}#h-rincian" aria-label="Tanggal sebelumnya"><x-icon name="nav-arrow-left" width="16" height="16" /></a>
                        @else
                            <span class="rekap-step-btn is-disabled" aria-hidden="true"><x-icon name="nav-arrow-left" width="16" height="16" /></span>
                        @endif
                        <span class="rekap-step-date">{{ $date->locale('id')->translatedFormat('l, j F Y') }}</span>
                        @if ($nextDate)
                            <a class="rekap-step-btn" href="{{ route('admin.rekap', array_merge($base, ['tanggal' => $nextDate->format('Y-m-d')])) }}#h-rincian" aria-label="Tanggal berikutnya"><x-icon name="nav-arrow-right" width="16" height="16" /></a>
                        @else
                            <span class="rekap-step-btn is-disabled" aria-hidden="true"><x-icon name="nav-arrow-right" width="16" height="16" /></span>
                        @endif
                    </div>
                    <a class="rekap-primary-btn" href="{{ route('admin.rekap.rincian', ['bagian' => $bagian, 'tanggal' => $date->format('Y-m-d')]) }}">
                        <span>Buka Rincian Harian</span>
                        <x-icon name="nav-arrow-right" width="18" height="18" />
                    </a>
                </section>
            @endif
        </main>

        @include('partials.footer')
    </div>

    <script src="{{ asset('js/rekap.js') }}"></script>
</body>

</html>
