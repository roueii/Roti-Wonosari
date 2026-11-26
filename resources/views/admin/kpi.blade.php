<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kelola KPI | Capaian.id - KPI Wonosari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@600&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}" />
</head>

<body>
    <div class="app-container">
        <!-- 1. Header Bar Kelola KPI -->
        <header class="revisi-header-bar">
            <div class="header-title-wrapper">
                <a href="{{ route('admin.dashboard') }}" class="back-btn" aria-label="Kembali ke beranda">
                    <x-icon name="arrow-left" width="20" height="20" />
                </a>
                <div class="header-titles">
                    <h1 class="header-page-title">Kelola KPI</h1>
                    <span class="header-page-subtitle">Atur hal yang dicatat tiap bagian</span>
                </div>
            </div>

            <button class="menu-btn" id="menuToggle" aria-label="Buka menu navigasi" aria-expanded="false">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </header>

        @if (session('status'))
            <div class="kpi-flash is-success" role="status">{{ session('status') }}</div>
        @endif
        @if (session('error') || $errors->any())
            <div class="kpi-flash is-error" role="alert">{{ session('error') ?? $errors->first() }}</div>
        @endif
        @if ($unavailable)
            <div class="kpi-flash is-error" role="alert">Data belum tersedia. Server sedang tidak dapat dihubungi, coba muat ulang beberapa saat lagi.</div>
        @endif

        <!-- 2. Tab Bagian (jabatan dari Backend) -->
        <div class="kpi-tabs-scroll-wrapper" role="tablist" aria-label="Pilih Bagian">
            @foreach ($panels as $p)
                <button type="button" class="kpi-tab-btn {{ $p['position_id'] === $active ? 'is-active' : '' }}" role="tab"
                    aria-selected="{{ $p['position_id'] === $active ? 'true' : 'false' }}"
                    data-position="{{ $p['position_id'] }}" onclick="switchDivision({{ $p['position_id'] }}, this)">
                    {{ $p['label'] }}
                </button>
            @endforeach
        </div>

        <!-- 3. Daftar hal yang dicatat per bagian -->
        <main class="kpi-groups-container" id="kpiGroupsContainer">
            @foreach ($panels as $p)
                <div class="kpi-division-panel {{ $p['position_id'] === $active ? 'is-visible' : 'is-hidden' }}"
                    data-division-panel="{{ $p['position_id'] }}" data-suggested-weight="{{ $p['suggested_weight'] }}">

                    @forelse ($p['groups'] as $group)
                        <div class="kpi-group-card is-open" id="group-{{ $p['slug'] }}-{{ $group['id'] }}">
                            <button type="button" class="kpi-group-header" onclick="toggleKpiGroup('group-{{ $p['slug'] }}-{{ $group['id'] }}')">
                                <div class="kpi-group-title-box">
                                    <h2 class="kpi-group-title">{{ $group['title'] }}</h2>
                                    <span class="kpi-group-subtitle">{{ $group['count_text'] }}</span>
                                </div>
                                <span class="kpi-group-chevron" aria-hidden="true">
                                    <x-icon name="nav-arrow-up" width="18" height="18" />
                                </span>
                            </button>

                            <div class="kpi-group-body">
                                <div class="kpi-items-list">
                                    @foreach ($group['items'] as $item)
                                        <div class="kpi-item-row" data-form='@json($item['form'])'>
                                            <div class="kpi-item-main">
                                                <div class="kpi-item-title">{{ $item['name'] }}</div>
                                                <div class="kpi-item-sub">{{ $item['subtitle'] }}</div>
                                            </div>
                                            <div class="kpi-item-side">
                                                <span class="kpi-type-badge {{ str_contains($item['badge'], 'Ceklis') ? 'is-ceklis' : 'is-angka' }}">{{ $item['badge'] }}</span>
                                                <button type="button" class="kpi-options-btn" aria-label="Opsi untuk {{ $item['name'] }}"
                                                    onclick="openItemOptions(event, this.closest('.kpi-item-row'))">&middot;&middot;&middot;</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <button type="button" class="kpi-add-group-action" onclick="openAddModal({{ $group['category_id'] ?? 'null' }})">
                                    <x-icon name="plus" width="16" height="16" />
                                    <span>Tambah di kelompok ini</span>
                                </button>
                            </div>
                        </div>
                    @empty
                        <p class="kpi-inactive-empty kpi-empty-panel">Belum ada hal yang dicatat untuk bagian ini. Tekan "Tambah hal yang dicatat" untuk mulai.</p>
                    @endforelse

                    <!-- Nonaktif: hal di daftar yang tidak dipakai bagian ini -->
                    <div class="kpi-inactive-accordion" id="inactive-{{ $p['slug'] }}">
                        <button type="button" class="kpi-inactive-header" onclick="toggleInactive('inactive-{{ $p['slug'] }}')">
                            <span class="kpi-inactive-chevron"><x-icon name="nav-arrow-right" width="16" height="16" aria-hidden="true" /></span>
                            <span class="kpi-inactive-title">Nonaktif ({{ count($p['inactive']) }})</span>
                        </button>
                        <div class="kpi-inactive-body">
                            @forelse ($p['inactive'] as $in)
                                <div class="kpi-inactive-row">
                                    <div class="kpi-item-main">
                                        <div class="kpi-item-title">{{ $in['name'] }}</div>
                                        <div class="kpi-item-sub">{{ $in['category'] }}{{ $in['is_active'] ? '' : ' · nonaktif di semua bagian' }}</div>
                                    </div>
                                    @if ($in['is_active'])
                                        <form method="POST" action="{{ route('admin.kpi.activate', $in['id']) }}">
                                            @csrf
                                            <input type="hidden" name="position_id" value="{{ $p['position_id'] }}">
                                            <button type="submit" class="kpi-activate-btn">Aktifkan</button>
                                        </form>
                                    @endif
                                </div>
                            @empty
                                <p class="kpi-inactive-empty">Semua hal di daftar sudah dipakai bagian ini.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </main>

        <!-- 4. Tombol Tambah -->
        @if ($panels)
            <div class="kpi-bottom-action-wrapper">
                <button type="button" class="btn-add-kpi-main" onclick="openAddModal(null)">
                    <x-icon name="plus" width="18" height="18" />
                    <span>Tambah hal yang dicatat</span>
                </button>
                <p class="kpi-swipe-hint">Geser tab untuk melihat bagian lainnya.</p>
            </div>
        @endif

        <!-- 5. Modal Tambah / Edit -->
        <div class="kpi-modal-overlay" id="addKpiModal" aria-hidden="true">
            <div class="kpi-modal-sheet" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
                <div class="kpi-modal-header">
                    <h3 class="kpi-modal-title" id="modalTitle">Tambah Hal yang Dicatat</h3>
                    <button type="button" class="kpi-modal-close" onclick="closeAddModal()" aria-label="Tutup form"><x-icon name="cancel" width="16" height="16" aria-hidden="true" /></button>
                </div>

                <form class="kpi-modal-form" id="kpiForm" method="POST" action="{{ route('admin.kpi.store') }}">
                    @csrf
                    <input type="hidden" name="position_id" id="kpiPositionInput">

                    <div class="form-group-field" id="existingRow">
                        <label for="kpiExistingSelect">Ambil dari daftar yang sudah ada</label>
                        <select id="kpiExistingSelect" name="existing_id" class="form-select-kpi" onchange="toggleExisting()">
                            <option value="">— Buat hal baru —</option>
                            @foreach ($catalogOptions as $c)
                                @if ($c['is_active'])
                                    <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="kpi-new-fields" id="newFields">
                        <div class="form-group-field">
                            <label for="kpiGroupSelect">Kelompok</label>
                            <select id="kpiGroupSelect" name="category_id" class="form-select-kpi">
                                @foreach ($categories as $c)
                                    <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group-field">
                            <label for="kpiNameInput">Nama hal yang dicatat</label>
                            <input type="text" id="kpiNameInput" name="name" class="form-input-kpi" maxlength="255" placeholder="Contoh: Mengontrol suhu oven" />
                        </div>

                        <div class="form-group-field">
                            <label for="kpiDescInput">Keterangan (opsional)</label>
                            <input type="text" id="kpiDescInput" name="description" class="form-input-kpi" maxlength="255" placeholder="Penjelasan singkat untuk karyawan" />
                        </div>

                        <div class="form-group-field">
                            <label>Tipe isian</label>
                            <div class="kpi-radio-toggle-group">
                                <label class="kpi-radio-card">
                                    <input type="radio" name="assessment_type" value="YES_NO" checked onchange="toggleTypeInputs()" />
                                    <span class="radio-card-content">
                                        <strong>Ceklis (Ya / Tidak)</strong>
                                        <small>Staf mencentang setelah tugas selesai</small>
                                    </span>
                                </label>
                                <label class="kpi-radio-card">
                                    <input type="radio" name="assessment_type" value="NUMERIC" onchange="toggleTypeInputs()" />
                                    <span class="radio-card-content">
                                        <strong>Angka / Nilai</strong>
                                        <small>Input angka seperti pcs, kg, persen (%)</small>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div id="numberOptionsRow" style="display: none;">
                            <div class="form-group-field">
                                <label for="kpiUnitInput">Satuan & target</label>
                                <div class="kpi-dual-inputs">
                                    <input type="text" id="kpiUnitInput" name="unit" class="form-input-kpi" maxlength="50" placeholder="Satuan (pcs, %, Rp)" />
                                    <input type="number" id="kpiTargetInput" name="target" class="form-input-kpi" min="0" step="any" placeholder="Target" />
                                </div>
                            </div>
                            <div class="form-group-field">
                                <label for="kpiCalcSelect">Cara menilai</label>
                                <select id="kpiCalcSelect" name="calculation_type" class="form-select-kpi">
                                    <option value="HIGHER_IS_BETTER">Makin tinggi makin baik (minimal target)</option>
                                    <option value="LOWER_IS_BETTER">Makin rendah makin baik (maksimal target)</option>
                                </select>
                            </div>
                        </div>

                        <label class="kpi-check-field">
                            <input type="hidden" name="requires_evidence" value="0">
                            <input type="checkbox" id="kpiEvidenceInput" name="requires_evidence" value="1">
                            <span>Wajib foto bukti</span>
                        </label>
                    </div>

                    <div class="form-group-field">
                        <label for="kpiWeightInput">Bobot & frekuensi</label>
                        <div class="kpi-dual-inputs">
                            <input type="number" id="kpiWeightInput" name="weight" class="form-input-kpi" min="0.01" max="100" step="0.01" required placeholder="Bobot %" />
                            <select id="kpiFreqSelect" name="frequency" class="form-select-kpi">
                                @foreach ($frequencies as $val => $lbl)
                                    <option value="{{ $val }}">{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <small class="kpi-field-hint">Bobot lain di bagian ini menyesuaikan otomatis agar total tetap 100%.</small>
                    </div>

                    <p class="kpi-field-hint" id="editNote" hidden>Perubahan nama, kelompok, tipe, dan foto berlaku di semua bagian yang memakai hal ini.</p>

                    <div class="kpi-modal-actions">
                        <button type="button" class="btn-kpi-cancel" onclick="closeAddModal()">Batal</button>
                        <button type="submit" class="btn-kpi-submit" id="kpiSubmitBtn">Simpan Hal Ini</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 6. Menu opsi (titik tiga) -->
        <div class="kpi-options-menu" id="kpiOptionsMenu">
            <button type="button" class="options-menu-item" onclick="openEditModal()">
                <x-icon name="edit-pencil" width="16" height="16" />
                <span>Edit Hal Ini</span>
            </button>
            <button type="button" class="options-menu-item" onclick="submitAction('deactivate')">
                <x-icon name="prohibition" width="16" height="16" />
                <span>Pindahkan ke Nonaktif</span>
            </button>
            <button type="button" class="options-menu-item is-danger" onclick="submitAction('destroy')">
                <x-icon name="trash" width="16" height="16" />
                <span>Hapus Permanen</span>
            </button>
        </div>
        <form method="POST" id="kpiActionForm" hidden>
            @csrf
            <input type="hidden" name="position_id" id="kpiActionPosition">
        </form>

        @include('admin.partials.drawer')
    </div>

    <script src="{{ asset('js/drawer.js') }}"></script>
    <script>
        const ROUTES = {
            store: @json(route('admin.kpi.store')),
            update: @json(route('admin.kpi.update', '__ID__')),
            deactivate: @json(route('admin.kpi.deactivate', '__ID__')),
            destroy: @json(route('admin.kpi.destroy', '__ID__')),
        };
        let activePosition = {{ (int) $active }};
        let currentItem = null;

        function switchDivision(positionId, btn) {
            activePosition = positionId;
            document.querySelectorAll('.kpi-tab-btn').forEach(b => {
                b.classList.toggle('is-active', b === btn);
                b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
            });
            document.querySelectorAll('.kpi-division-panel').forEach(panel => {
                const show = panel.dataset.divisionPanel == positionId;
                panel.classList.toggle('is-visible', show);
                panel.classList.toggle('is-hidden', !show);
            });
            history.replaceState(null, '', '?bagian=' + positionId);
        }

        function toggleKpiGroup(groupId) {
            const card = document.getElementById(groupId);
            if (!card) return;
            card.classList.toggle('is-open');
            card.classList.toggle('is-collapsed');
        }

        function toggleInactive(id) {
            document.getElementById(id)?.classList.toggle('is-open');
        }

        function el(id) { return document.getElementById(id); }

        function toggleTypeInputs() {
            const numeric = document.querySelector('input[name="assessment_type"]:checked')?.value === 'NUMERIC';
            el('numberOptionsRow').style.display = numeric ? 'block' : 'none';
        }

        function toggleExisting() {
            const useExisting = !!el('kpiExistingSelect').value;
            el('newFields').style.display = useExisting ? 'none' : '';
            el('kpiNameInput').required = !useExisting;
        }

        function suggestedWeight() {
            return document.querySelector(`[data-division-panel="${activePosition}"]`)?.dataset.suggestedWeight || 100;
        }

        function showModal(title) {
            el('modalTitle').textContent = title;
            el('kpiPositionInput').value = activePosition;
            el('addKpiModal').classList.add('is-active');
            el('addKpiModal').setAttribute('aria-hidden', 'false');
        }

        function openAddModal(categoryId) {
            const f = el('kpiForm');
            f.reset();
            f.action = ROUTES.store;
            el('existingRow').style.display = '';
            el('editNote').hidden = true;
            el('kpiSubmitBtn').textContent = 'Simpan Hal Ini';
            if (categoryId) el('kpiGroupSelect').value = categoryId;
            el('kpiWeightInput').value = suggestedWeight();
            toggleExisting();
            toggleTypeInputs();
            showModal('Tambah Hal yang Dicatat');
            el('kpiNameInput').focus();
        }

        function openEditModal() {
            el('kpiOptionsMenu').classList.remove('is-visible');
            if (!currentItem) return;
            const d = currentItem;
            const f = el('kpiForm');
            f.reset();
            f.action = ROUTES.update.replace('__ID__', d.indicator_id);
            el('existingRow').style.display = 'none';
            el('kpiExistingSelect').value = '';
            toggleExisting();
            el('kpiGroupSelect').value = d.category_id;
            el('kpiNameInput').value = d.name;
            el('kpiDescInput').value = d.description || '';
            document.querySelector(`input[name="assessment_type"][value="${d.assessment_type}"]`).checked = true;
            el('kpiUnitInput').value = d.unit || '';
            el('kpiTargetInput').value = d.assessment_type === 'NUMERIC' ? d.target : '';
            el('kpiCalcSelect').value = d.calculation_type;
            el('kpiEvidenceInput').checked = d.requires_evidence;
            el('kpiWeightInput').value = d.weight;
            el('kpiFreqSelect').value = d.frequency;
            el('editNote').hidden = false;
            el('kpiSubmitBtn').textContent = 'Simpan Perubahan';
            toggleTypeInputs();
            showModal('Edit Hal yang Dicatat');
        }

        function closeAddModal() {
            el('addKpiModal').classList.remove('is-active');
            el('addKpiModal').setAttribute('aria-hidden', 'true');
        }

        function openItemOptions(event, row) {
            event.stopPropagation();
            currentItem = JSON.parse(row.dataset.form);
            const menu = el('kpiOptionsMenu');
            const rect = event.currentTarget.getBoundingClientRect();
            menu.style.top = `${rect.bottom + window.scrollY + 6}px`;
            menu.style.left = `${Math.max(8, Math.min(rect.left - 130, window.innerWidth - 190))}px`;
            menu.classList.add('is-visible');
        }

        document.addEventListener('click', function (e) {
            const menu = el('kpiOptionsMenu');
            if (menu && !menu.contains(e.target)) menu.classList.remove('is-visible');
        });

        function submitAction(kind) {
            el('kpiOptionsMenu').classList.remove('is-visible');
            if (!currentItem) return;
            const msg = kind === 'destroy'
                ? `Hapus "${currentItem.name}" secara permanen? Bila masih dipakai bagian lain, hal ini hanya dilepas dari bagian ini.`
                : `Pindahkan "${currentItem.name}" ke Nonaktif? Karyawan bagian ini tidak lagi mengisinya.`;
            if (!confirm(msg)) return;
            const f = el('kpiActionForm');
            f.action = ROUTES[kind].replace('__ID__', currentItem.indicator_id);
            el('kpiActionPosition').value = activePosition;
            f.submit();
        }
    </script>
</body>

</html>
