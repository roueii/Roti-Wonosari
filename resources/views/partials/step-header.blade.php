{{--
    Header halaman Isi Capaian karyawan (Figma "SHARED HEADER: Close Icon + Step Progress Dots", mis. 1031:2436)
    + judul halaman (Figma "Section Heading").
    Parameter: $closeUrl, $badge, $step, $total, $stepLabel, $title, $subtitle (opsional)
--}}
<header class="step-hd">
    <div class="step-hd-top">
        <a href="{{ $closeUrl }}" class="step-hd-close" aria-label="Batal dan kembali ke dashboard">
            <x-icon name="cancel" width="24" height="24" />
        </a>
        <span class="step-hd-badge">{{ $badge }}</span>
    </div>

    <div class="step-hd-progress" role="progressbar" aria-valuemin="1" aria-valuemax="{{ $total }}" aria-valuenow="{{ $step }}" aria-label="Langkah {{ $step }} dari {{ $total }}">
        @for ($i = 1; $i <= $total; $i++)
            <span class="step-hd-seg {{ $i < $step ? 'is-done' : '' }} {{ $i === $step ? 'is-current' : '' }}"></span>
        @endfor
    </div>

    <div class="step-hd-meta">
        <strong>Langkah {{ $step }} dari {{ $total }}</strong>
        <span>{!! $stepLabel !!}</span>
    </div>
</header>

<div class="step-hd-heading">
    <h1>{!! $title !!}</h1>
    @if (!empty($subtitle))
        <p>{!! $subtitle !!}</p>
    @endif
</div>

@if ($errors->has('step') || $errors->has('backend'))
    <div class="step-error" role="alert">
        <x-icon name="warning-circle" width="18" height="18" aria-hidden="true" />
        <span>{{ $errors->first('step') ?: $errors->first('backend') }}</span>
    </div>
@endif
