<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Display & Kebersihan (Langkah 5/6) | Frontliner Capaian.id</title>
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
            'step' => 5,
            'total' => 6,
            'stepLabel' => 'Display & Kebersihan',
            'title' => 'Display & Kebersihan',
            'subtitle' => 'Input total perolehan transaksi kasir shift hari ini.',
        ])

        <!-- Form Pengisian Langkah 5 -->
        <form action="{{ route('frontliner.display.post') }}" method="POST" id="formLangkah5" enctype="multipart/form-data">
            @csrf

            <!-- Card 1: Indikator Penilaian -->
            <section class="fl-step-card" style="margin-bottom: 20px;">
                <div class="fl-step-card-header">
                    <div class="fl-step-card-titles">
                        <h2 class="fl-step-card-title">Indikator Penilaian</h2>
                        <span class="fl-step-card-sub">Centang indikator yang dipenuhi hari ini</span>
                    </div>
                </div>

                <div class="fl-check-list">
                    @foreach($indicators as $index => $item)
                    <label class="driver-check-card {{ $item['checked'] ? 'is-checked' : '' }}">
                        <input type="checkbox" name="indicators[]" value="{{ $item['id'] }}" {{ $item['checked'] ? 'checked' : '' }} class="ds-visually-hidden indicator-checkbox" />
                        <span class="driver-check-box" aria-hidden="true"><x-icon name="check" class="driver-check-icon" /></span>
                        <span class="driver-check-info">
                            <strong class="driver-check-title">{{ $item['title'] }}</strong>
                            <span class="driver-check-sub">{{ $item['desc'] }}</span>
                        </span>
                    </label>
                    @endforeach
                </div>
            </section>

            <!-- Unggah Bukti Foto (komponen bersama, Figma 1031:2491) -->
            @include('partials.upload-foto', ['inputId' => 'fotoInputDisplay', 'role' => 'Frontliner'])

            <!-- Warning Notice Box Sesuai Figma Revisi -->
            <div class="step-notice">
                <x-icon name="warning-circle" width="16" height="16" aria-hidden="true" />
                <p>Semua indikator penilaian dan 1 foto bukti wajib dilengkapi sebelum Anda dapat melangkah ke tahap <strong>Selanjutnya</strong>.</p>
            </div>

            <!-- Footer Buttons Sesuai Figma Revisi -->
            <div class="fl-step-footer">
                <div class="fl-step-btn-row">
                    <a href="{{ route('frontliner.kasir') }}" class="fl-btn-back">Kembali</a>
                    <button type="submit" class="fl-btn-next">
                        <span>Lanjut</span>
                        <x-icon name="arrow-right" width="18" height="18" />
                    </button>
                </div>
                <span class="fl-step-footer-text">Langkah 5 dari 6</span>
            </div>
        </form>
    </div>

    <script src="{{ asset('js/upload-foto.js') }}"></script>

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
