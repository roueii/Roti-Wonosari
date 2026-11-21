<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SOP Administrasi | Capaian.id - Driver</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@600&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/driver.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}" />
</head>

<body>
    <div class="app-container">
        <!-- Header langkah + judul (Figma SHARED HEADER) -->
        @include('partials.step-header', [
            'closeUrl' => route('driver.dashboard'),
            'badge' => 'DRIVER',
            'step' => 2,
            'total' => 4,
            'stepLabel' => 'SOP Administrasi',
            'title' => 'SOP Administrasi',
            'subtitle' => 'Input Harian Divisi Logistik &amp; Distribusi Driver',
        ])

        <form action="{{ route('driver.administrasi.post') }}" method="POST" id="formAdministrasi" enctype="multipart/form-data">
            @csrf

            <!-- 3. KARTU 1: INDIKATOR PENILAIAN -->
            <section class="fl-step-card" aria-labelledby="heading-indikator-admin">
                <div class="fl-step-card-header">
                    <h2 class="fl-step-card-title" id="heading-indikator-admin">INDIKATOR PENILAIAN</h2>
                    <span class="fl-step-card-tag">WAJIB</span>
                </div>
                <p class="fl-step-card-sub">Centang indikator yang dipenuhi hari ini:</p>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <!-- Item 1 -->
                    <label class="driver-check-card {{ !empty($stepData['surat_jalan']) ? 'is-checked' : '' }}">
                        <input class="ds-visually-hidden" type="checkbox" name="indicators[surat_jalan]" value="1" {{ !empty($stepData['surat_jalan']) ? 'checked' : '' }} />
                        <div class="driver-check-box">
                            <x-icon name="check" class="driver-check-icon" />
                        </div>
                        <div class="driver-check-info">
                            <span class="driver-check-title">Membawa surat jalan/nota</span>
                            <span class="driver-check-sub">Memastikan kelengkapan dokumen pengiriman sebelum armada berangkat.</span>
                        </div>
                    </label>

                    <!-- Item 2 -->
                    <label class="driver-check-card {{ !empty($stepData['tanda_tangan']) ? 'is-checked' : '' }}">
                        <input class="ds-visually-hidden" type="checkbox" name="indicators[tanda_tangan]" value="1" {{ !empty($stepData['tanda_tangan']) ? 'checked' : '' }} />
                        <div class="driver-check-box">
                            <x-icon name="check" class="driver-check-icon" />
                        </div>
                        <div class="driver-check-info">
                            <span class="driver-check-title">Memastikan barang diterima &amp; ditandatangani</span>
                            <span class="driver-check-sub">Tanda tangan bukti serah terima sah dari penerima atau kepala toko outlet.</span>
                        </div>
                    </label>

                    <!-- Item 3 -->
                    <label class="driver-check-card {{ !empty($stepData['kembalikan_dokumen']) ? 'is-checked' : '' }}">
                        <input class="ds-visually-hidden" type="checkbox" name="indicators[kembalikan_dokumen]" value="1" {{ !empty($stepData['kembalikan_dokumen']) ? 'checked' : '' }} />
                        <div class="driver-check-box">
                            <x-icon name="check" class="driver-check-icon" />
                        </div>
                        <div class="driver-check-info">
                            <span class="driver-check-title">Mengembalikan dokumen lengkap</span>
                            <span class="driver-check-sub">Surat jalan kembali dalam kondisi bersih, utuh, dan diserahkan ke bagian admin.</span>
                        </div>
                    </label>
                </div>
            </section>

            <!-- Unggah Bukti Foto (komponen bersama, Figma 1031:2491) -->
            @include('partials.upload-foto', ['inputId' => 'fotoDriverAdministrasi', 'role' => 'Driver'])

            <!-- 5. Dashed Notice Bottom Box Sesuai Figma -->
            <div class="driver-notice-dashed">
                <x-icon name="warning-circle" width="18" height="18" />
                <span>Semua indikator penilaian dan 1 foto bukti wajib dilengkapi sebelum Anda dapat melangkah ke tahap Selanjutnya.</span>
            </div>

            <!-- 6. Action Buttons (Kembali & Lanjut) Sesuai Figma -->
            <div class="fl-btn-row">
                <a href="{{ route('driver.pengiriman') }}" class="fl-btn-back" style="text-decoration: none; text-align: center;">
                    Kembali
                </a>
                <button type="submit" class="fl-btn-next">
                    <span>Lanjut</span>
                    <x-icon name="arrow-right" width="18" height="18" aria-hidden="true" />
                </button>
            </div>

            <div style="text-align: center; margin-top: 12px; margin-bottom: 24px; font-size: var(--fs-12); color: var(--neutral-400);">
                Langkah 2 dari 4
            </div>
        </form>
    </div>

    <!-- Script Interaktif -->
    <script>
        document.querySelectorAll('.driver-check-card').forEach(card => {
            card.addEventListener('click', function(e) {
                const input = this.querySelector('input[type="checkbox"]');
                if (e.target !== input) {
                    e.preventDefault(); // cegah toggle ganda dari <label>
                    input.checked = !input.checked;
                }
                if (input.checked) {
                    this.classList.add('is-checked');
                } else {
                    this.classList.remove('is-checked');
                }
            });
        });

        function handleFileSelect(e) {
            const files = e.target.files;
            const container = document.getElementById('photoChipsList');
            for (let i = 0; i < files.length; i++) {
                const chip = document.createElement('div');
                chip.className = 'fl-photo-chip';
                chip.innerHTML = `
                    <x-icon name="media-image" class="fl-photo-chip-icon" />
                    <span class="fl-photo-chip-name">${files[i].name}</span>
                    <button type="button" class="fl-photo-chip-close" onclick="this.parentElement.remove()" aria-label="Hapus file"><x-icon name="cancel" width="16" height="16" aria-hidden="true" /></button>
                `;
                container.appendChild(chip);
            }
        }
    </script>
    <script src="{{ asset('js/upload-foto.js') }}"></script>
</body>

</html>

