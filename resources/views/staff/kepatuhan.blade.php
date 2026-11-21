<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kepatuhan SOP & Disiplin (Langkah 6/6) | Frontliner Capaian.id</title>
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
        <!-- Header langkah + judul (Figma SHARED HEADER) -->
        @include('partials.step-header', [
            'closeUrl' => route('frontliner.dashboard'),
            'badge' => 'FRONTLINER',
            'step' => 6,
            'total' => 6,
            'stepLabel' => 'Kepatuhan SOP',
            'title' => 'Kepatuhan SOP & Disiplin',
            'subtitle' => 'Input total perolehan transaksi kasir shift hari ini.',
        ])

        <!-- Form Pengisian Langkah 6 (Final Submit) -->
        <form action="{{ route('frontliner.kepatuhan.post') }}" method="POST" id="formLangkah6">
            @csrf

            <!-- Card: Indikator Penilaian (Wajib) -->
            <section class="fl-step-card" style="margin-bottom: 20px;">
                <div class="fl-step-card-header">
                    <div class="fl-step-card-titles">
                        <h2 class="fl-step-card-title">Indikator Penilaian</h2>
                        <span class="fl-step-card-sub">Centang indikator yang dipenuhi hari ini sesuai standar operasional dapur produksi.</span>
                    </div>
                    <span class="fl-step-required-badge">WAJIB</span>
                </div>

                <!-- Subheading 1: Kepatuhan SOP -->
                <div style="margin-top: 16px; margin-bottom: 8px;">
                    <span style="font-size: var(--fs-12); font-weight: var(--fw-semibold); color: var(--neutral-900); text-transform: uppercase; letter-spacing: 0.04em;">Kepatuhan SOP</span>
                </div>

                <div class="fl-check-list">
                    @foreach($sopIndicators as $item)
                    <label class="driver-check-card {{ $item['checked'] ? 'is-checked' : '' }}">
                        <input type="checkbox" name="sop[]" value="{{ $item['id'] }}" {{ $item['checked'] ? 'checked' : '' }} class="ds-visually-hidden indicator-checkbox" />
                        <span class="driver-check-box" aria-hidden="true"><x-icon name="check" class="driver-check-icon" /></span>
                        <span class="driver-check-info">
                            <strong class="driver-check-title">{{ $item['title'] }}</strong>
                            <span class="driver-check-sub">{{ $item['desc'] }}</span>
                        </span>
                    </label>
                    @endforeach
                </div>

                <!-- Subheading 2: Disiplin -->
                <div style="margin-top: 18px; margin-bottom: 8px;">
                    <span style="font-size: var(--fs-12); font-weight: var(--fw-semibold); color: var(--neutral-900); text-transform: uppercase; letter-spacing: 0.04em;">Disiplin</span>
                </div>

                <div class="fl-check-list">
                    @foreach($disiplinIndicators as $item)
                    <label class="driver-check-card {{ $item['checked'] ? 'is-checked' : '' }}">
                        <input type="checkbox" name="disiplin[]" value="{{ $item['id'] }}" {{ $item['checked'] ? 'checked' : '' }} class="ds-visually-hidden indicator-checkbox" />
                        <span class="driver-check-box" aria-hidden="true"><x-icon name="check" class="driver-check-icon" /></span>
                        <span class="driver-check-info">
                            <strong class="driver-check-title">{{ $item['title'] }}</strong>
                            <span class="driver-check-sub">{{ $item['desc'] }}</span>
                        </span>
                    </label>
                    @endforeach
                </div>
            </section>

            <!-- Warning Notice Box Sesuai Figma Revisi -->
            <div class="step-notice">
                <x-icon name="warning-circle" width="16" height="16" aria-hidden="true" />
                <p>Pastikan semua data input harian telah sesuai sebelum mengirim capaian. Laporan yang sudah dikirim akan langsung diverifikasi oleh <strong>Store Manager</strong></p>
            </div>

            <!-- Footer Buttons Sesuai Figma Revisi -->
            <div class="fl-step-footer">
                <div class="fl-step-btn-row">
                    <a href="{{ route('frontliner.display') }}" class="fl-btn-back">Kembali</a>
                    <button type="submit" class="fl-btn-next">
                        <span>Kirim Semua</span>
                    </button>
                </div>
                <div class="step-draft-row">
                    <button type="submit" form="formLangkah6" formaction="{{ route('frontliner.draft.post') }}" class="ck-draft-link">
                        Simpan sebagai Draf Sementara
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Script Checkbox Interactivity -->
    <script>
        document.querySelectorAll('.driver-check-card').forEach(card => {
            const checkbox = card.querySelector('.indicator-checkbox');

            card.addEventListener('click', function (e) {
                if (e.target !== checkbox) {
                    e.preventDefault(); // cegah toggle ganda dari <label>
                    checkbox.checked = !checkbox.checked;
                }
                if (checkbox.checked) {
                    card.classList.add('is-checked');
                } else {
                    card.classList.remove('is-checked');
                }
            });
        });
    </script>
</body>

</html>
