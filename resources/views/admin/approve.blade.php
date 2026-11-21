<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Approve Laporan | Capaian.id - KPI Wonosari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@600&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}" />
</head>

<body>
    <div class="app-container">
        <!-- 1. Header Bar Approve Laporan -->
        <header class="revisi-header-bar">
            <div class="header-title-wrapper">
                <a href="{{ route('admin.dashboard') }}" class="back-btn" aria-label="Kembali ke beranda">
                    <x-icon name="arrow-left" width="20" height="20" />
                </a>
                <div class="header-titles">
                    <h1 class="header-page-title">Approve Laporan</h1>
                    <span class="header-page-subtitle">Laporan yang sudah disetujui Store Manager</span>
                </div>
            </div>

            <button class="menu-btn" id="menuToggle" aria-label="Buka menu navigasi" aria-expanded="false">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </header>

        @if (session('status'))
            <div class="toast-inline success" role="status" style="background: var(--success-100); border: 1px solid var(--success-500); color: var(--success-700); border-radius: 12px; padding: 10px 14px; font-size: var(--fs-14); font-weight: 600;">{{ session('status') }}</div>
        @endif
        @if (session('error') || $errors->any())
            <div role="alert" style="background: var(--error-100); border: 1px solid var(--error-500); color: var(--error-700); border-radius: 12px; padding: 10px 14px; font-size: var(--fs-14); font-weight: 600;">{{ session('error') ?? $errors->first() }}</div>
        @endif

        <!-- 2. Horizontal Filter Tabs / Divisi -->
        <div class="kpi-tabs-scroll-wrapper" role="tablist" aria-label="Filter Divisi Laporan">
            <button type="button" 
                    class="kpi-tab-btn is-active" 
                    data-filter="all" 
                    onclick="filterReports('all', this)">
                Semua Divisi
            </button>
            <button type="button" 
                    class="kpi-tab-btn" 
                    data-filter="Store Manager" 
                    onclick="filterReports('Store Manager', this)">
                Store Manager
            </button>
            <button type="button" 
                    class="kpi-tab-btn" 
                    data-filter="Frontliner" 
                    onclick="filterReports('Frontliner', this)">
                Frontliner
            </button>
            <button type="button" 
                    class="kpi-tab-btn" 
                    data-filter="Produksi" 
                    onclick="filterReports('Produksi', this)">
                Produksi
            </button>
            <button type="button" 
                    class="kpi-tab-btn" 
                    data-filter="Driver" 
                    onclick="filterReports('Driver', this)">
                Driver
            </button>
            <button type="button" 
                    class="kpi-tab-btn" 
                    data-filter="Sales & Marketing" 
                    onclick="filterReports('Sales & Marketing', this)">
                Sales & Marketing
            </button>
        </div>

        <!-- 3. Kotak Ringkasan Antrean Approval -->
        <div class="account-summary-card">
            <div class="summary-card-left">
                <div class="approval-summary-icon">
                    <x-icon name="verified-user" width="20" height="20" />
                </div>
                <div class="approval-summary-text">
                    <strong class="approval-summary-count" id="reportsCountText">{{ isset($liveReports) ? count($liveReports) : 0 }} Laporan</strong>
                    <span class="approval-summary-sub">Menunggu verifikasi & approval final Admin</span>
                </div>
            </div>
        </div>

        <!-- 4. Daftar Kartu Laporan Approval -->
        <main class="approval-reports-list" id="approvalReportsList">
            @isset($liveReports)
                @if (count($liveReports) === 0)
                    <p class="approval-empty-state">Belum ada laporan yang menunggu persetujuan akhir. Laporan muncul di sini setelah disetujui Store Manager.</p>
                @endif

                @foreach ($liveReports as $i => $report)
                    <article class="approval-card is-expanded" id="report-card-live-{{ $i }}" data-division="{{ $report['division'] }}">
                        <div class="approval-card-header">
                            <div class="approval-card-person">
                                <h2 class="approval-person-name">{{ $report['name'] }}</h2>
                            </div>
                            <span class="approval-status-badge">
                                <x-icon name="check" width="12" height="12" />
                                <span>{{ $report['is_manager_report'] ? 'Laporan SM' : 'Disetujui SM' }}</span>
                            </span>
                        </div>

                        <div class="approval-meta-row">
                            <x-icon name="check" width="14" height="14" style="color: var(--primary-600);" />
                            <span>{{ $report['approval_meta'] }} &middot; Laporan {{ $report['report_label'] }}</span>
                        </div>

                        @if (count($report['evidences']))
                            <div class="approval-attachment-card">
                                <div class="attachment-left">
                                    <div class="attachment-thumb-icon"><x-icon name="media-image" width="20" height="20" /></div>
                                    <div class="attachment-meta">
                                        <strong>Foto Bukti ({{ count($report['evidences']) }} File)</strong>
                                        <small>{{ collect($report['evidences'])->pluck('file_name')->implode(', ') }}</small>
                                    </div>
                                </div>
                                <a href="{{ $report['evidences'][0]['url'] }}" target="_blank" rel="noopener" class="attachment-preview-btn">Pratinjau Berkas</a>
                            </div>
                        @endif

                        <div class="approval-checklist-box">
                            <div class="checklist-box-header">
                                <span class="checklist-box-title">RINCIAN INDIKATOR CHECKLIST</span>
                                <span class="checklist-box-progress">{{ $report['checklist_summary'] }}</span>
                            </div>
                            <div class="checklist-items-stack">
                                @foreach ($report['checklists'] as $item)
                                    <div class="checklist-item-entry">
                                        <div class="checklist-checkbox {{ $item['is_checked'] ? 'is-checked' : '' }}" aria-hidden="true">
                                            @if ($item['is_checked'])<x-icon name="check" width="13" height="13" />@endif
                                        </div>
                                        <div class="checklist-entry-info">
                                            <div class="checklist-entry-title">{{ $item['title'] }}</div>
                                            <div class="checklist-entry-sub">{{ $item['target_sub'] }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if (! empty($report['sm_note']))
                                <div class="sm-note-bubble">
                                    <div class="sm-note-header"><x-icon name="page" width="14" height="14" /><span>CATATAN STORE MANAGER</span></div>
                                    <p class="sm-note-text">&ldquo;{{ $report['sm_note'] }}&rdquo;</p>
                                </div>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('admin.approve.decide', ['userId' => $report['user_id'], 'date' => $report['date']]) }}">
                            @csrf
                            <div class="admin-note-wrapper">
                                <label for="adminNoteLive{{ $i }}" class="admin-note-label">Catatan Admin / Owner (wajib saat menolak)</label>
                                <input type="text" id="adminNoteLive{{ $i }}" name="notes" maxlength="255" class="admin-note-input" placeholder="Tulis catatan revisi atau apresiasi..." />
                            </div>
                            <div class="approval-card-actions">
                                <button type="submit" name="decision" value="REJECTED" class="btn-reject-action">
                                    <x-icon name="cancel" width="14" height="14" />
                                    <span>Tolak (Revisi)</span>
                                </button>
                                <button type="submit" name="decision" value="APPROVED" class="btn-approve-action">
                                    <x-icon name="check" width="15" height="15" />
                                    <span>Approve Final</span>
                                </button>
                            </div>
                        </form>
                    </article>
                @endforeach
            @else
                <p class="approval-summary-sub" style="text-align:center;padding:24px 0;">Data belum tersedia. Server sedang tidak dapat dihubungi, coba muat ulang beberapa saat lagi.</p>
            @endisset
        </main>

        <!-- 5. Info / Disclaimer Sinkronisasi Rapor Bulanan -->
        <footer class="approval-sync-disclaimer">
            <div class="disclaimer-icon">
                <x-icon name="info-empty" width="18" height="18" />
            </div>
            <div class="disclaimer-text">
                Laporan yang disetujui final akan langsung disinkronisasi ke <strong>Rapor Bulanan</strong> dan menjadi basis kalkulasi bonus KPI karyawan secara otomatis.
            </div>
        </footer>

        <!-- 6. Modal Pratinjau Berkas Bukti -->
        <div class="kpi-modal-overlay" id="proofModal" aria-hidden="true">
            <div class="kpi-modal-sheet" role="dialog" aria-modal="true" aria-labelledby="proofTitle">
                <div class="kpi-modal-header">
                    <h3 class="kpi-modal-title" id="proofTitle">Pratinjau Foto Bukti</h3>
                    <button type="button" class="kpi-modal-close" onclick="closeProofModal()" aria-label="Tutup preview"><x-icon name="cancel" width="16" height="16" aria-hidden="true" /></button>
                </div>
                <div class="proof-modal-body">
                    <div class="proof-photo-box">
                        <div class="photo-placeholder-card">
                            <x-icon name="media-image" width="40" height="40" style="color: var(--primary-600);" />
                            <span class="photo-title" id="proofPhotoName">Foto Display Kasir & Setoran</span>
                            <small class="photo-sub">Diambil melalui kamera aplikasi &middot; Geotag Outlet Malioboro</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close-proof" onclick="closeProofModal()">Tutup Pratinjau</button>
                </div>
            </div>
        </div>

        <!-- 7. Side Menu Drawer Partial -->
        @include('admin.partials.drawer')


        <!-- Toast: hanya muncul sesaat setelah ada aksi -->
        <div class="toast-msg" id="toastMsg" role="status" aria-live="polite"></div>
    </div>

    <!-- Script Interaktif Approve Laporan -->
    <script src="{{ asset('js/drawer.js') }}"></script>
    <script>
        let totalRemaining = 6;

        // Filter daftar laporan berdasarkan divisi
        function filterReports(division, btnElement) {
            document.querySelectorAll('.kpi-tabs-scroll-wrapper .kpi-tab-btn').forEach(btn => {
                btn.classList.remove('is-active');
            });
            if (btnElement) {
                btnElement.classList.add('is-active');
            }

            const cards = document.querySelectorAll('.approval-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const cardDiv = card.getAttribute('data-division');
                if (division === 'all' || cardDiv === division) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            showToast(`Menampilkan ${visibleCount} laporan untuk filter ${division === 'all' ? 'Semua Divisi' : division}`);
        }

        // Aksi Approve Final
        function approveReport(id, name) {
            const card = document.getElementById(`report-card-${id}`);
            if (!card) return;

            card.style.transition = 'all 0.35s cubic-bezier(0.16, 1, 0.3, 1)';
            card.style.transform = 'scale(0.96) translateY(-10px)';
            card.style.opacity = '0';

            setTimeout(() => {
                card.remove();
                totalRemaining = Math.max(0, totalRemaining - 1);
                updateCountDisplay();
                showToast(`✓ Laporan ${name} berhasil di-approve final dan disinkronkan ke Rapor Bulanan!`);
            }, 350);
        }

        // Aksi Tolak (Revisi)
        function rejectReport(id, name) {
            const noteInput = document.getElementById(`adminNote${id}`);
            const noteVal = noteInput ? noteInput.value.trim() : '';

            const confirmReject = confirm(`Kirim kembali laporan ${name} ke antrean revisi?`);
            if (!confirmReject) return;

            const card = document.getElementById(`report-card-${id}`);
            if (!card) return;

            card.style.transition = 'all 0.35s cubic-bezier(0.16, 1, 0.3, 1)';
            card.style.transform = 'scale(0.96) translateX(-20px)';
            card.style.opacity = '0';

            setTimeout(() => {
                card.remove();
                totalRemaining = Math.max(0, totalRemaining - 1);
                updateCountDisplay();
                showToast(`Laporan ${name} dikembalikan untuk diperbaiki (Perlu Revisi).`);
            }, 350);
        }

        function updateCountDisplay() {
            const countText = document.getElementById('reportsCountText');
            if (countText) {
                countText.textContent = `${totalRemaining} Laporan`;
            }
        }

        // Modal Preview Berkas
        function previewAttachment(title) {
            const modal = document.getElementById('proofModal');
            const proofTitle = document.getElementById('proofTitle');
            const photoName = document.getElementById('proofPhotoName');

            if (proofTitle) proofTitle.textContent = title;
            if (photoName) photoName.textContent = title;

            if (modal) {
                modal.classList.add('is-active');
                modal.setAttribute('aria-hidden', 'false');
            }
        }

        function closeProofModal() {
            const modal = document.getElementById('proofModal');
            if (modal) {
                modal.classList.remove('is-active');
                modal.setAttribute('aria-hidden', 'true');
            }
        }

        // Toast feedback
        let toastTimer;
        function showToast(msg) {
            const toast = document.getElementById('toastMsg');
            if (!toast) return;

            clearTimeout(toastTimer);
            toast.textContent = msg;
            toast.classList.add('show');
            toastTimer = setTimeout(() => {
                toast.classList.remove('show');
            }, 3400);
        }

        // Drawer navigation
        document.addEventListener('DOMContentLoaded', function () {
        });
    </script>
</body>

</html>
