<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kebersihan Area Kerja | Capaian.id - Produksi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@600&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/produksi.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}" />
</head>

<body>
    <div class="app-container">
        <!-- Header langkah + judul (Figma SHARED HEADER) -->
        @include('partials.step-header', [
            'closeUrl' => route('produksi.dashboard'),
            'badge' => 'PRODUKSI',
            'step' => 5,
            'total' => 5,
            'stepLabel' => 'Kebersihan Area Kerja',
            'title' => 'Kebersihan Area Kerja',
            'subtitle' => 'Input Harian Divisi Produksi Roti',
        ])

        <form action="{{ route('produksi.kebersihan_area.post') }}" method="POST" id="formKebersihanArea" enctype="multipart/form-data">
            @csrf

            <!-- 3. KARTU INDIKATOR PENILAIAN (6 ITEM) -->
            <section class="fl-step-card" aria-labelledby="heading-kebersihan-area">
                <div class="fl-step-card-header">
                    <h2 class="fl-step-card-title" id="heading-kebersihan-area">INDIKATOR PENILAIAN</h2>
                    <span class="fl-step-card-tag">WAJIB</span>
                </div>
                <p class="fl-step-card-sub">Centang indikator yang dipenuhi hari ini sesuai checklist sanitasi &amp; standar kebersihan dapur produksi.</p>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <!-- Item 1 -->
                    <label class="produksi-check-card {{ !empty($stepData['meja_bersih']) ? 'is-checked' : '' }}">
                        <input class="ds-visually-hidden" type="checkbox" name="indicators[meja_bersih]" value="1" {{ !empty($stepData['meja_bersih']) ? 'checked' : '' }} />
                        <div class="produksi-check-box">
                            <x-icon name="check" class="produksi-check-icon" />
                        </div>
                        <div class="produksi-check-info">
                            <span class="produksi-check-title">Meja bersih</span>
                            <span class="produksi-check-sub">Meja kerja stainless steril, bebas sisa adonan tepung &amp; remah</span>
                        </div>
                    </label>

                    <!-- Item 2 -->
                    <label class="produksi-check-card {{ !empty($stepData['oven_bersih']) ? 'is-checked' : '' }}">
                        <input class="ds-visually-hidden" type="checkbox" name="indicators[oven_bersih]" value="1" {{ !empty($stepData['oven_bersih']) ? 'checked' : '' }} />
                        <div class="produksi-check-box">
                            <x-icon name="check" class="produksi-check-icon" />
                        </div>
                        <div class="produksi-check-info">
                            <span class="produksi-check-title">Oven bersih</span>
                            <span class="produksi-check-sub">Kaca deck, loyang pembakar, dan kerak dalam ruang bakar bersih</span>
                        </div>
                    </label>

                    <!-- Item 3 -->
                    <label class="produksi-check-card {{ !empty($stepData['mixer_bersih']) ? 'is-checked' : '' }}">
                        <input class="ds-visually-hidden" type="checkbox" name="indicators[mixer_bersih]" value="1" {{ !empty($stepData['mixer_bersih']) ? 'checked' : '' }} />
                        <div class="produksi-check-box">
                            <x-icon name="check" class="produksi-check-icon" />
                        </div>
                        <div class="produksi-check-info">
                            <span class="produksi-check-title">Mixer bersih</span>
                            <span class="produksi-check-sub">Bowl spiral mixer, hook pengaduk, dan bodi luar dicuci kering</span>
                        </div>
                    </label>

                    <!-- Item 4 -->
                    <label class="produksi-check-card {{ !empty($stepData['lantai_bersih']) ? 'is-checked' : '' }}">
                        <input class="ds-visually-hidden" type="checkbox" name="indicators[lantai_bersih]" value="1" {{ !empty($stepData['lantai_bersih']) ? 'checked' : '' }} />
                        <div class="produksi-check-box">
                            <x-icon name="check" class="produksi-check-icon" />
                        </div>
                        <div class="produksi-check-info">
                            <span class="produksi-check-title">Lantai bersih</span>
                            <span class="produksi-check-sub">Disapu dan dipel dengan disinfektan dapur tidak licin/berminyak</span>
                        </div>
                    </label>

                    <!-- Item 5 -->
                    <label class="produksi-check-card {{ !empty($stepData['rak_bersih']) ? 'is-checked' : '' }}">
                        <input class="ds-visually-hidden" type="checkbox" name="indicators[rak_bersih]" value="1" {{ !empty($stepData['rak_bersih']) ? 'checked' : '' }} />
                        <div class="produksi-check-box">
                            <x-icon name="check" class="produksi-check-icon" />
                        </div>
                        <div class="produksi-check-info">
                            <span class="produksi-check-title">Rak bersih</span>
                            <span class="produksi-check-sub">Cooling rack &amp; rak penyimpanan bahan tertata rapi tanpa debu</span>
                        </div>
                    </label>

                    <!-- Item 6 -->
                    <label class="produksi-check-card {{ !empty($stepData['tempat_sampah']) ? 'is-checked' : '' }}">
                        <input class="ds-visually-hidden" type="checkbox" name="indicators[tempat_sampah]" value="1" {{ !empty($stepData['tempat_sampah']) ? 'checked' : '' }} />
                        <div class="produksi-check-box">
                            <x-icon name="check" class="produksi-check-icon" />
                        </div>
                        <div class="produksi-check-info">
                            <span class="produksi-check-title">Tempat sampah tertutup</span>
                            <span class="produksi-check-sub">Plastik sampah diganti baru, tutup pedal berfungsi rapat</span>
                        </div>
                    </label>
                </div>
            </section>

            <!-- Unggah Bukti Foto (komponen bersama, Figma 1031:2491) -->
            @include('partials.upload-foto', ['inputId' => 'fotoProduksiKebersihanArea', 'role' => 'Produksi'])

            <!-- 5. Dashed Notice Bottom Box Sesuai Figma -->
            <div class="produksi-notice-dashed">
                <x-icon name="warning-circle" width="18" height="18" />
                <span>Pastikan semua data input harian telah sesuai sebelum mengirim capaian. Laporan yang sudah dikirim akan langsung diverifikasi oleh <strong>Store Manager</strong></span>
            </div>

            <!-- 6. Action Buttons (Kembali & Kirim Semua) Sesuai Figma -->
            <div class="fl-btn-row">
                <a href="{{ route('produksi.kepatuhan_sop') }}" class="fl-btn-back" style="text-decoration: none; text-align: center;">
                    Kembali
                </a>
                <button type="submit" class="fl-btn-next">
                    <span>Kirim Semua</span>
                </button>
            </div>

            <!-- Sub Action: Simpan sebagai Draf Sementara -->
            <div class="step-draft-row">
                <button type="button" onclick="saveDraftProduksi()" class="ck-draft-link">
                    Simpan sebagai Draf Sementara
                </button>
            </div>
        </form>
    </div>

    <!-- Script Interaktif -->
    <script>
        document.querySelectorAll('.produksi-check-card').forEach(card => {
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

        function handleFileSelectArea(e) {
            const files = e.target.files;
            const container = document.getElementById('photoChipsListArea');
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

        function saveDraftProduksi() {
            fetch("{{ route('produksi.draft.post') }}", {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                // Kirim checklist yang sedang dicentang (tanpa file foto)
                body: (() => {
                    const data = new FormData(document.getElementById('formKebersihanArea'));
                    data.delete('photos[]');
                    return data;
                })()
            }).then(res => {
                if (!res.ok) throw new Error('Gagal menyimpan draf');
                alert('Capaian produksi berhasil disimpan sebagai draf sementara!');
                window.location.href = "{{ route('produksi.dashboard') }}";
            }).catch(() => {
                alert('Gagal menyimpan draf. Periksa koneksi Anda lalu coba lagi.');
            });
        }
    </script>
    <script src="{{ asset('js/upload-foto.js') }}"></script>
</body>

</html>

