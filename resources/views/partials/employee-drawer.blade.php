{{--
    Drawer / navbar karyawan (Figma Menu: Frontliner 1099:5974, Store Manager 1099:5907).
    Parameter:
      $roleLabel  teks peran di kartu profil (mis. "Frontliner")
      $nav        [['url' =>, 'label' =>, 'icon' =>, 'active' => bool, 'size' => 16|20], ...]
--}}
<div class="drawer-overlay" id="drawerOverlay" aria-hidden="true">
    <div class="drawer-backdrop" id="drawerBackdrop"></div>
    <aside class="drawer-sheet sm-drawer" id="drawerSheet" role="dialog" aria-modal="true" aria-label="Menu Navigasi {{ $roleLabel }}">
        <div class="sm-drawer-header">
            <div class="sm-drawer-brand">
                <strong>CAPAIAN.ID</strong>
                <span>KPI Toko Roti Wonosari</span>
            </div>
            <button class="sm-drawer-close" id="drawerCloseBtn" type="button" aria-label="Tutup menu navigasi">
                <x-icon name="cancel" width="24" height="24" />
            </button>
        </div>

        <div class="sm-drawer-section">
            <div class="sm-drawer-user">
                <span class="sm-drawer-avatar" aria-hidden="true"><x-icon name="user" width="24" height="24" /></span>
                <span class="sm-drawer-user-meta">
                    <strong>{{ $user['name'] ?? 'User' }}</strong>
                    <span>{{ $roleLabel }}</span>
                </span>
            </div>
        </div>

        <nav class="sm-drawer-nav" aria-label="Menu {{ $roleLabel }}">
            @foreach ($nav as $item)
                <a href="{{ $item['url'] }}" class="sm-drawer-link {{ $item['active'] ? 'is-active' : '' }}" @if ($item['active']) aria-current="page" @endif>
                    <span class="sm-drawer-link-left">
                        <span class="sm-drawer-link-icon"><x-icon :name="$item['icon']" :width="$item['size'] ?? 16" :height="$item['size'] ?? 16" aria-hidden="true" /></span>
                        <span>{{ $item['label'] }}</span>
                    </span>
                    <x-icon name="nav-arrow-right" width="16" height="16" class="sm-drawer-chevron" aria-hidden="true" />
                </a>
            @endforeach
        </nav>

        <div class="sm-drawer-footer">
            <span>KPI Toko Roti Wonosari</span>
        </div>
    </aside>
</div>
