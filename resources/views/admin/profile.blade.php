<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profil Saya | Capaian.id - KPI Wonosari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@600&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}" />
</head>

<body>
    <div class="app-container">
        <!-- 1. Header Profil (Kembali, Judul, Hamburger Menu) -->
        <header class="profile-header-bar">
            <a href="{{ route('admin.dashboard') }}" class="back-btn" aria-label="Kembali ke beranda">
                <x-icon name="arrow-left" width="20" height="20" />
            </a>

            <h1 class="header-page-title">Profil</h1>

            <button class="menu-btn" id="menuToggle" aria-label="Buka menu navigasi" aria-expanded="false">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </header>

        <!-- 2. Kartu Ringkasan Profil Utama -->
        <section class="profile-card">
            <div class="profile-avatar-large">
                <x-icon name="user" />
            </div>

            <h2 class="profile-display-name">{{ $user['name'] ?? 'User' }}</h2>
            <div class="profile-role-badge">{{ $user['role'] ?? 'ADMIN' }}</div>

            <div class="profile-status-indicator">
                <span class="status-dot"></span>
                <span class="status-text">{{ $user['status'] ?? 'Akun Aktif' }}</span>
            </div>
        </section>

        <!-- 3. Bagian Informasi Akun -->
        <section class="account-info-section">
            <h3 class="section-subheading">INFORMASI AKUN</h3>

            <div class="account-info-card">
                <!-- Baris Email -->
                <div class="info-row">
                    <div class="info-label-group">
                        <div class="info-icon-box">
                            <x-icon name="mail" />
                        </div>
                        <span class="info-label">Email</span>
                    </div>
                    <span class="info-value">{{ $user['email'] }}</span>
                </div>
            </div>
        </section>

        <!-- 4. Ubah Email (Figma: "Informasi Akun Section" > UBAH EMAIL) -->
        @php $emailOpen = $errors->has('email'); @endphp
        <section class="account-info-section" aria-labelledby="ubah-email-heading">
            <h3 class="section-subheading" id="ubah-email-heading">UBAH EMAIL</h3>

            @if (session('status'))
                <p class="email-status-msg" role="status">{{ session('status') }}</p>
            @endif

            <div class="account-info-card email-edit-card {{ $emailOpen ? 'is-open' : '' }}" id="emailEditCard">
                <button type="button" class="info-row email-toggle" id="emailToggle" aria-expanded="{{ $emailOpen ? 'true' : 'false' }}" aria-controls="emailEditForm">
                    <span class="info-label-group">
                        <span class="info-icon-box"><x-icon name="mail" /></span>
                        <span class="info-label">{{ $user['email'] }}</span>
                    </span>
                    <x-icon name="nav-arrow-down" width="20" height="20" class="email-toggle-caret" />
                </button>

                <form class="email-edit-form" id="emailEditForm" method="POST" action="{{ route('admin.profile.email') }}" @unless ($emailOpen) hidden @endunless>
                    @csrf
                    <label for="new-email" class="email-edit-label">Email Baru</label>
                    <div class="email-input-box {{ $errors->has('email') ? 'is-invalid' : '' }}">
                        <x-icon name="mail" width="20" height="20" />
                        <input type="email" id="new-email" name="email" value="{{ old('email') }}" placeholder="Masukkan email baru"
                            autocomplete="email" required @if ($errors->has('email')) aria-invalid="true" aria-describedby="new-email-error" @endif />
                    </div>
                    @error('email')
                        <span class="email-edit-error" id="new-email-error">{{ $message }}</span>
                    @enderror
                    <div class="email-edit-actions">
                        <button type="button" class="email-btn-cancel" id="emailCancel">Batal</button>
                        <button type="submit" class="email-btn-save">Simpan</button>
                    </div>
                </form>
            </div>
        </section>

        <!-- 4. Tombol Logout (Pindah ke Profil Sesuai Permintaan) -->
        <form action="{{ route('logout') }}" method="POST" class="logout-form-wrapper">
            @csrf
            <button type="submit" class="logout-action-btn">
                <x-icon name="log-out" width="20" height="20" />
                <span>Keluar (Logout)</span>
            </button>
        </form>
        @include('partials.profile-footer')


        <!-- Include Side Menu Drawer (Gambar 1) -->
        @include('admin.partials.drawer')
    </div>

    <!-- Toast Element -->
    <div class="toast-msg" id="toastMsg" role="alert" aria-live="polite"></div>

    <script src="{{ asset('js/drawer.js') }}"></script>
    <script>
        const toastEl = document.getElementById('toastMsg');
        let toastTimeout;

        // Buka / tutup form Ubah Email
        const emailCard = document.getElementById('emailEditCard');
        const emailToggle = document.getElementById('emailToggle');
        const emailForm = document.getElementById('emailEditForm');

        function setEmailOpen(open) {
            emailForm.hidden = !open;
            emailCard.classList.toggle('is-open', open);
            emailToggle.setAttribute('aria-expanded', String(open));
            if (open) document.getElementById('new-email').focus();
        }

        emailToggle.addEventListener('click', () => setEmailOpen(emailForm.hidden));
        document.getElementById('emailCancel').addEventListener('click', () => {
            emailForm.reset();
            setEmailOpen(false);
            emailToggle.focus();
        });

        function showToast(message) {
            clearTimeout(toastTimeout);
            toastEl.textContent = message;
            toastEl.classList.add('show');
            toastTimeout = setTimeout(() => {
                toastEl.classList.remove('show');
            }, 2400);
        }
    </script>
</body>

</html>
