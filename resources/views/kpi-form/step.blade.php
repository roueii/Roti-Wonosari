@extends('checklist.layout')

@section('title', $title)

@php
    $fmt = fn ($n) => str_ends_with($s = number_format((float) $n, 2, ',', '.'), ',00') ? substr($s, 0, -3) : $s;
    $freq = ['DAILY' => 'Harian', 'WEEKLY' => 'Mingguan', 'MONTHLY' => 'Bulanan'];
@endphp

@section('content')
    @include('partials.step-header', [
        'closeUrl' => $dashboardUrl,
        'badge' => $cfg['badge'],
        'step' => $stepNumber,
        'total' => $totalSteps,
        'stepLabel' => e($title),
        'title' => e($title),
        'subtitle' => 'Isi capaian ' . e($cfg['page_title']) . ' untuk periode ini',
    ])

    <form action="{{ $postUrl }}" method="POST" id="stepForm" @if ($needsPhoto) enctype="multipart/form-data" @endif>
        @csrf

        <section class="ck-card" aria-labelledby="heading-indikator">
            <div class="ck-card-header">
                <h2 class="ck-card-title" id="heading-indikator">INDIKATOR PENILAIAN</h2>
                <span class="ck-tag">WAJIB</span>
            </div>
            <p class="ck-card-sub">{{ $allLocked ? 'Semua isian di langkah ini sudah dikirim dan sedang/selesai ditinjau.' : 'Centang yang sudah dipenuhi dan isi angka capaian:' }}</p>

            <div class="ck-check-list">
                @foreach ($items as $key => $item)
                    @php
                        $meta = $freq[$item['frequency']] ?? '';
                        if ($item['requires_evidence']) $meta .= ' · Wajib foto';
                    @endphp

                    @if ($item['type'] === 'YES_NO')
                        <label class="driver-check-card {{ $item['value'] ? 'is-checked' : '' }} {{ $item['locked'] ? 'is-locked' : '' }}">
                            <input type="checkbox" class="ck-check-input" name="answers[{{ $key }}]" value="1" @checked($item['value']) @disabled($item['locked']) />
                            <span class="driver-check-box" aria-hidden="true">
                                <x-icon name="check" class="driver-check-icon" />
                            </span>
                            <span class="driver-check-info">
                                <span class="driver-check-title">{{ $item['title'] }}</span>
                                @if ($item['desc'])
                                    <span class="driver-check-sub">{{ $item['desc'] }}</span>
                                @endif
                                <span class="kf-item-meta">{{ $item['locked'] ? 'Sudah dikirim' : $meta }}</span>
                                @if ($item['review_note'])
                                    <span class="kf-item-note">Catatan revisi: {{ $item['review_note'] }}</span>
                                @endif
                            </span>
                        </label>
                    @else
                        <div class="kf-number-card {{ $item['locked'] ? 'is-locked' : '' }}">
                            <label class="driver-check-title" for="ans-{{ $key }}">{{ $item['title'] }}</label>
                            @if ($item['desc'])
                                <span class="driver-check-sub">{{ $item['desc'] }}</span>
                            @endif
                            <div class="kf-number-row">
                                <input type="number" id="ans-{{ $key }}" name="answers[{{ $key }}]" class="kf-number-input" inputmode="decimal" step="any" min="0"
                                    value="{{ $item['value'] !== null ? rtrim(rtrim(number_format((float) $item['value'], 2, '.', ''), '0'), '.') : '' }}"
                                    placeholder="0" @disabled($item['locked']) @unless($item['locked']) required @endunless />
                                @if ($item['unit'])
                                    <span class="kf-number-unit">{{ $item['unit'] }}</span>
                                @endif
                            </div>
                            <span class="kf-item-meta">
                                {{ $item['locked'] ? 'Sudah dikirim' : ($item['lower_better'] ? 'Maksimal ' : 'Target ') . $fmt($item['target']) . ' ' . $item['unit'] . ' · ' . $meta }}
                            </span>
                            @if ($item['review_note'])
                                <span class="kf-item-note">Catatan revisi: {{ $item['review_note'] }}</span>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </section>

        @if ($needsPhoto)
            @php
                $hasPhoto = collect($items)->filter(fn ($i) => $i['requires_evidence'] && ! $i['locked'])
                    ->every(fn ($i) => $i['evidences'] > 0 || $i['stashed'] > 0);
            @endphp
            @include('partials.upload-foto', ['inputId' => 'fotoKpi', 'role' => $cfg['page_title']])
            @if ($hasPhoto)
                <p class="kf-photo-note">Foto bukti sudah tersimpan. Unggah lagi hanya bila ingin menggantinya.</p>
            @endif
        @endif

        @if ($errors->any() && ! $errors->has('step'))
            <div class="ck-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="ck-step-footer">
            <div class="ck-btn-row">
                @if ($prevUrl)
                    <a href="{{ $prevUrl }}" class="fl-btn-back">Kembali</a>
                @endif
                <button type="submit" class="fl-btn-next">
                    @if ($isLast)
                        <span>Kirim Semua Capaian</span>
                    @else
                        <span>Lanjut</span>
                        <x-icon name="arrow-right" width="18" height="18" aria-hidden="true" />
                    @endif
                </button>
            </div>
            @if ($isLast)
                <button type="button" class="ck-draft-link" id="saveDraftBtn">Simpan sebagai Draf Sementara</button>
            @else
                <span class="ck-step-footer-text">Langkah {{ $stepNumber }} dari {{ $totalSteps }}</span>
            @endif
        </div>
    </form>
@endsection

@push('scripts')
    @if ($needsPhoto)
        <script src="{{ asset('js/upload-foto.js') }}"></script>
    @endif
    <script>
        document.querySelectorAll('.ck-check-input').forEach(input => {
            input.addEventListener('change', () => {
                input.closest('.driver-check-card').classList.toggle('is-checked', input.checked);
            });
        });

        document.getElementById('saveDraftBtn')?.addEventListener('click', async () => {
            const data = new FormData(document.getElementById('stepForm'));
            data.delete('photos[]');
            data.append('step', @js($step));
            try {
                const res = await fetch(@js($draftUrl), { method: 'POST', headers: { 'Accept': 'application/json' }, body: data });
                const json = await res.json();
                alert(json.message || 'Draf tersimpan.');
                window.location.href = json.redirect || @js($dashboardUrl);
            } catch (e) {
                alert('Gagal menyimpan draf. Periksa koneksi Anda lalu coba lagi.');
            }
        });
    </script>
@endpush
