@extends('checklist.layout')

@section('title', 'Isi Capaian')

@section('content')
    <header class="step-hd">
        <div class="step-hd-top">
            <a href="{{ $dashboardUrl }}" class="step-hd-close" aria-label="Kembali ke dashboard">
                <x-icon name="cancel" width="24" height="24" />
            </a>
            <span class="step-hd-badge">{{ $cfg['badge'] }}</span>
        </div>
    </header>

    <div class="step-hd-heading">
        <h1>Isi Capaian</h1>
        <p>{{ $cfg['page_title'] }}</p>
    </div>

    <section class="ck-card" role="status">
        <div class="ck-card-header">
            <h2 class="ck-card-title">BELUM ADA ISIAN</h2>
        </div>
        <p class="ck-card-sub">{{ $message }}</p>
    </section>

    <div class="ck-step-footer">
        <div class="ck-btn-row">
            <a href="{{ $dashboardUrl }}" class="fl-btn-back">Kembali ke Beranda</a>
        </div>
    </div>
@endsection
