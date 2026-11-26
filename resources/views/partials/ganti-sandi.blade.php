{{--
    Kartu "Ganti Kata Sandi" di halaman Profil (semua role).
    Admin mengganti sendiri; karyawan meminta Admin meresetnya (Backend belum punya endpoint ganti sandi sendiri).
--}}
@php
    $bisa = \App\Http\Controllers\KataSandiController::bisaGanti(session('user.role'));
    $err = $errors->getBag('sandi');
    $open = $err->any();
@endphp
<section class="pw-section" aria-labelledby="pw-heading">
    <h3 class="pw-heading" id="pw-heading">KEAMANAN AKUN</h3>
    <div class="pw-card {{ $open ? 'is-open' : '' }}">
        <button type="button" class="pw-toggle" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="pwBody"
            onclick="const c=this.closest('.pw-card');c.classList.toggle('is-open');this.setAttribute('aria-expanded',c.classList.contains('is-open'));">
            <span class="pw-toggle-left">
                <span class="pw-icon"><x-icon name="shield-check" width="18" height="18" /></span>
                <span>Ganti Kata Sandi</span>
            </span>
            <x-icon name="nav-arrow-down" width="20" height="20" class="pw-caret" />
        </button>

        <div class="pw-body" id="pwBody">
            @if ($bisa)
                <form method="POST" action="{{ route('profil.kata-sandi') }}" class="pw-form">
                    @csrf
                    <label for="pw-current">Kata sandi saat ini</label>
                    <input type="password" id="pw-current" name="current_password" required autocomplete="current-password" />
                    @if ($err->has('current_password'))
                        <span class="pw-error">{{ $err->first('current_password') }}</span>
                    @endif

                    <label for="pw-new">Kata sandi baru</label>
                    <input type="password" id="pw-new" name="password" required minlength="8" autocomplete="new-password" placeholder="Minimal 8 karakter" />

                    <label for="pw-confirm">Ulangi kata sandi baru</label>
                    <input type="password" id="pw-confirm" name="password_confirmation" required minlength="8" autocomplete="new-password" />
                    @if ($err->has('password'))
                        <span class="pw-error">{{ $err->first('password') }}</span>
                    @endif

                    <p class="pw-hint">Setelah diganti, Anda akan diminta masuk lagi dengan kata sandi baru.</p>
                    <button type="submit" class="pw-submit">Simpan Kata Sandi Baru</button>
                </form>
            @else
                <p class="pw-hint">Untuk mengganti kata sandi, hubungi Admin. Admin dapat mereset kata sandi Anda di menu <strong>Kelola Akun</strong>, lalu memberitahukan kata sandi barunya kepada Anda.</p>
            @endif
        </div>
    </div>
</section>
