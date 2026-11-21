{{--
    Kartu "Unggah Bukti Foto" bersama untuk semua role (Figma 1234:1524 / 1031:2491).
    Parameter: $inputId (id unik input file), $role (teks "Dokumentasi ...", default Store Manager),
               $tips (opsional, daftar ciri foto yang benar).
--}}
@php
    $tips = $tips ?? [
        'Foto diambil hari ini di toko, bukan foto lama.',
        'Objek utama terlihat jelas, tidak buram, dan tidak terpotong.',
        'Pencahayaan cukup — hindari foto gelap atau silau.',
        'Ambil dari depan dengan jarak yang memperlihatkan keseluruhan area.',
    ];
    $contohFoto = file_exists(public_path('img/contoh-foto-bukti.jpg')) ? asset('img/contoh-foto-bukti.jpg') : null;
@endphp
<section class="sm-upload-card" data-upload aria-labelledby="{{ $inputId }}-title">
    <div class="sm-upload-head">
        <div>
            <h2 class="sm-upload-title" id="{{ $inputId }}-title">Unggah Bukti Foto</h2>
            <p class="sm-upload-sub">Dokumentasi <span>{{ $role ?? 'Store Manager' }}</span></p>
        </div>
        <span class="sm-upload-badge">Wajib sebelum lanjut</span>
    </div>

    <label class="sm-upload-drop" for="{{ $inputId }}">
        <input type="file" id="{{ $inputId }}" name="photos[]" multiple accept="image/jpeg,image/png" class="ds-visually-hidden" data-upload-input />
        <span class="sm-upload-camera" aria-hidden="true"><x-icon name="camera" width="24" height="24" /></span>
        <strong class="sm-upload-drop-title">Ketuk untuk unggah foto</strong>
        <span class="sm-upload-drop-sub">Format JPG/PNG, maks. 5MB.</span>
    </label>

    <p class="sm-upload-error" data-upload-error role="alert" hidden></p>
    <ul class="sm-upload-files" data-upload-list aria-label="Foto yang dipilih"></ul>

    <button type="button" class="sm-upload-example" data-upload-example>
        <x-icon name="eye-empty" width="16" height="16" aria-hidden="true" />
        <span>Lihat contoh foto yang benar</span>
    </button>

    {{-- Pop-up: contoh foto yang benar --}}
    <div class="sm-photo-modal" data-modal="contoh" hidden>
        <div class="sm-photo-backdrop" data-modal-close></div>
        <div class="sm-photo-sheet" role="dialog" aria-modal="true" aria-labelledby="{{ $inputId }}-contoh">
            <div class="sm-photo-sheet-head">
                <h3 id="{{ $inputId }}-contoh">Contoh Foto yang Benar</h3>
                <button type="button" class="sm-photo-close" data-modal-close aria-label="Tutup"><x-icon name="cancel" width="20" height="20" /></button>
            </div>
            @if ($contohFoto)
                <img class="sm-photo-img" src="{{ $contohFoto }}" alt="Contoh foto bukti yang benar" />
            @endif
            <ul class="sm-photo-tips">
                @foreach ($tips as $tip)
                    <li><x-icon name="check-circle" width="18" height="18" aria-hidden="true" /><span>{{ $tip }}</span></li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Pop-up: pratinjau foto yang diunggah --}}
    <div class="sm-photo-modal" data-modal="preview" hidden>
        <div class="sm-photo-backdrop" data-modal-close></div>
        <div class="sm-photo-sheet" role="dialog" aria-modal="true" aria-label="Pratinjau foto">
            <div class="sm-photo-sheet-head">
                <h3 data-preview-name>Foto</h3>
                <button type="button" class="sm-photo-close" data-modal-close aria-label="Tutup"><x-icon name="cancel" width="20" height="20" /></button>
            </div>
            <img class="sm-photo-img" data-preview-img alt="" />
        </div>
    </div>
</section>
