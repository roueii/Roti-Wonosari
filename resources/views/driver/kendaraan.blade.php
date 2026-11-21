<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kendaraan & BBM | Capaian.id - Driver</title>
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
            'step' => 3,
            'total' => 4,
            'stepLabel' => 'Kendaraan &amp; BBM',
            'title' => 'Kendaraan &amp; BBM',
            'subtitle' => 'Input Harian Divisi Logistik &amp; Distribusi Driver',
        ])

        <form action="{{ route('driver.kendaraan.post') }}" method="POST" id="formKendaraan">
            @csrf

            <!-- 3. KARTU 1: KENDARAAN (6 INDIKATOR) -->
            <section class="fl-step-card" aria-labelledby="heading-kendaraan">
                <div class="fl-step-card-header">
                    <h2 class="fl-step-card-title" id="heading-kendaraan">KENDARAAN</h2>
                    <span class="fl-step-card-tag">WAJIB</span>
                </div>
                <p class="fl-step-card-sub">Centang indikator kelaikan dan kebersihan kendaraan hari ini:</p>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <!-- Item 1 -->
                    <label class="driver-check-card {{ !empty($stepData['kebersihan']) ? 'is-checked' : '' }}">
                        <input class="ds-visually-hidden" type="checkbox" name="indicators[kebersihan]" value="1" {{ !empty($stepData['kebersihan']) ? 'checked' : '' }} />
                        <div class="driver-check-box">
                            <x-icon name="check" class="driver-check-icon" />
                        </div>
                        <div class="driver-check-info">
                            <span class="driver-check-title">Kendaraan bersih setiap hari</span>
                            <span class="driver-check-sub">Interior kabin dan boks muatan dicuci/dibersihkan secara berkala.</span>
                        </div>
                    </label>

                    <!-- Item 2 -->
                    <label class="driver-check-card {{ !empty($stepData['cek_rem']) ? 'is-checked' : '' }}">
                        <input class="ds-visually-hidden" type="checkbox" name="indicators[cek_rem]" value="1" {{ !empty($stepData['cek_rem']) ? 'checked' : '' }} />
                        <div class="driver-check-box">
                            <x-icon name="check" class="driver-check-icon" />
                        </div>
                        <div class="driver-check-info">
                            <span class="driver-check-title">Cek rem pada kendaraan</span>
                            <span class="driver-check-sub">Pemeriksaan fungsi keselamatan sebelum armada dijalankan.</span>
                        </div>
                    </label>

                    <!-- Item 3 -->
                    <label class="driver-check-card {{ !empty($stepData['cek_oli']) ? 'is-checked' : '' }}">
                        <input class="ds-visually-hidden" type="checkbox" name="indicators[cek_oli]" value="1" {{ !empty($stepData['cek_oli']) ? 'checked' : '' }} />
                        <div class="driver-check-box">
                            <x-icon name="check" class="driver-check-icon" />
                        </div>
                        <div class="driver-check-info">
                            <span class="driver-check-title">Cek oli pada kendaraan</span>
                            <span class="driver-check-sub">Pemeriksaan fungsi keselamatan sebelum armada dijalankan.</span>
                        </div>
                    </label>

                    <!-- Item 4 -->
                    <label class="driver-check-card {{ !empty($stepData['cek_ban']) ? 'is-checked' : '' }}">
                        <input class="ds-visually-hidden" type="checkbox" name="indicators[cek_ban]" value="1" {{ !empty($stepData['cek_ban']) ? 'checked' : '' }} />
                        <div class="driver-check-box">
                            <x-icon name="check" class="driver-check-icon" />
                        </div>
                        <div class="driver-check-info">
                            <span class="driver-check-title">Cek ban pada kendaraan</span>
                            <span class="driver-check-sub">Pemeriksaan fungsi keselamatan sebelum armada dijalankan.</span>
                        </div>
                    </label>

                    <!-- Item 5 -->
                    <label class="driver-check-card {{ !empty($stepData['cek_lampu']) ? 'is-checked' : '' }}">
                        <input class="ds-visually-hidden" type="checkbox" name="indicators[cek_lampu]" value="1" {{ !empty($stepData['cek_lampu']) ? 'checked' : '' }} />
                        <div class="driver-check-box">
                            <x-icon name="check" class="driver-check-icon" />
                        </div>
                        <div class="driver-check-info">
                            <span class="driver-check-title">Cek lampu pada kendaraan</span>
                            <span class="driver-check-sub">Pemeriksaan fungsi keselamatan sebelum armada dijalankan.</span>
                        </div>
                    </label>

                    <!-- Item 6 -->
                    <label class="driver-check-card {{ !empty($stepData['lapor_kerusakan']) ? 'is-checked' : '' }}">
                        <input class="ds-visually-hidden" type="checkbox" name="indicators[lapor_kerusakan]" value="1" {{ !empty($stepData['lapor_kerusakan']) ? 'checked' : '' }} />
                        <div class="driver-check-box">
                            <x-icon name="check" class="driver-check-icon" />
                        </div>
                        <div class="driver-check-info">
                            <span class="driver-check-title">Melaporkan kerusakan sebelum parah</span>
                            <span class="driver-check-sub">Jika hari ini ada kerusakan pada kendaraan segera lapor</span>
                        </div>
                    </label>
                </div>
            </section>

            <!-- 4. KARTU 2: BBM & RUTE -->
            <section class="fl-step-card" aria-labelledby="heading-bbm">
                <div class="fl-step-card-header">
                    <h2 class="fl-step-card-title" id="heading-bbm">BBM &amp; RUTE</h2>
                    <span class="fl-step-card-tag">WAJIB</span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <!-- Item 1 -->
                    <label class="driver-check-card {{ !empty($stepData['tidak_boros_bbm']) ? 'is-checked' : '' }}">
                        <input class="ds-visually-hidden" type="checkbox" name="indicators[tidak_boros_bbm]" value="1" {{ !empty($stepData['tidak_boros_bbm']) ? 'checked' : '' }} />
                        <div class="driver-check-box">
                            <x-icon name="check" class="driver-check-icon" />
                        </div>
                        <div class="driver-check-info">
                            <span class="driver-check-title">Tidak boros BBM</span>
                            <span class="driver-check-sub">Penggunaan BBM hari ini wajar dan sesuai dengan jarak tempuh yang dilalui.</span>
                        </div>
                    </label>

                    <!-- Item 2 -->
                    <label class="driver-check-card {{ !empty($stepData['tidak_pribadi']) ? 'is-checked' : '' }}">
                        <input class="ds-visually-hidden" type="checkbox" name="indicators[tidak_pribadi]" value="1" {{ !empty($stepData['tidak_pribadi']) ? 'checked' : '' }} />
                        <div class="driver-check-box">
                            <x-icon name="check" class="driver-check-icon" />
                        </div>
                        <div class="driver-check-info">
                            <span class="driver-check-title">Tidak menggunakan kendaraan untuk kepentingan pribadi</span>
                            <span class="driver-check-sub">Armada operasional hanya digunakan untuk rute distribusi resmi.</span>
                        </div>
                    </label>

                    <!-- Item 3 -->
                    <label class="driver-check-card {{ !empty($stepData['rute_efektif']) ? 'is-checked' : '' }}">
                        <input class="ds-visually-hidden" type="checkbox" name="indicators[rute_efektif]" value="1" {{ !empty($stepData['rute_efektif']) ? 'checked' : '' }} />
                        <div class="driver-check-box">
                            <x-icon name="check" class="driver-check-icon" />
                        </div>
                        <div class="driver-check-info">
                            <span class="driver-check-title">Menggunakan rute efektif</span>
                            <span class="driver-check-sub">Mematuhi urutan pengantaran dan efisiensi jarak tempuh.</span>
                        </div>
                    </label>
                </div>
            </section>

            <!-- 5. Dashed Notice Bottom Box Sesuai Figma -->
            <div class="driver-notice-dashed">
                <x-icon name="warning-circle" width="18" height="18" />
                <span>Ketuk baris untuk menandai ceklis selesai dikerjakan.</span>
            </div>

            <!-- 6. Action Buttons (Kembali & Lanjut) Sesuai Figma -->
            <div class="fl-btn-row">
                <a href="{{ route('driver.administrasi') }}" class="fl-btn-back" style="text-decoration: none; text-align: center;">
                    Kembali
                </a>
                <button type="submit" class="fl-btn-next">
                    <span>Lanjut</span>
                    <x-icon name="arrow-right" width="18" height="18" aria-hidden="true" />
                </button>
            </div>

            <div style="text-align: center; margin-top: 12px; margin-bottom: 24px; font-size: var(--fs-12); color: var(--neutral-400);">
                Langkah 3 dari 4
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
    </script>
</body>

</html>

