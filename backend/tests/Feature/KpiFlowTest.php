<?php

namespace Tests\Feature;

use App\Models\KpiCategory;
use App\Models\KpiReview;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Alur KPI ujung ke ujung seperti yang dipakai FE (KPI-WONOSARI):
 * login -> isi capaian -> review Store Manager -> persetujuan akhir Admin.
 */
class KpiFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function tokenFor(string $email): string
    {
        $this->app['auth']->forgetGuards();

        return $this->withoutHeader('Authorization')
            ->postJson('/api/login', ['email' => $email, 'password' => 'password'])
            ->assertOk()
            ->json('token');
    }

    private function api(string $token): static
    {
        // Guard Sanctum menyimpan user antar-request dalam satu test; reset saat ganti akun
        $this->app['auth']->forgetGuards();

        return $this->withHeader('Authorization', "Bearer {$token}");
    }

    /** Isi semua indikator harian karyawan untuk hari ini lalu kirim; kembalikan tanggal laporan. */
    private function submitDaily(string $email): string
    {
        Storage::fake('public');
        $token = $this->tokenFor($email);

        $kpis = $this->api($token)->getJson('/api/my-kpis')->assertOk()->json('data');
        $this->assertNotEmpty($kpis, "Tidak ada KPI untuk {$email}");

        $items = collect($kpis)->map(fn ($k) => [
            'position_kpi_id' => $k['id'],
            'answer_yes_no'   => $k['input_type'] === 'YES_NO' ? true : null,
            'numeric_value'   => $k['input_type'] === 'NUMERIC' ? 1 : null,
        ])->values()->all();

        // Simpan draft dulu, unggah bukti untuk indikator yang wajib bukti, lalu kirim
        $draft = $this->api($token)->postJson('/api/my-kpis/submissions', ['items' => $items, 'submit' => false])
            ->assertOk()->json('data');

        foreach ($kpis as $k) {
            if (! empty($k['requires_evidence'])) {
                $submissionId = collect($draft)->firstWhere('position_kpi_id', $k['id'])['id'] ?? null;
                if ($submissionId) {
                    $this->api($token)->post("/api/kpi-submissions/{$submissionId}/evidences", [
                        'file' => UploadedFile::fake()->image('bukti.jpg'),
                    ], ['Accept' => 'application/json'])->assertSuccessful();
                }
            }
        }

        $this->api($token)->postJson('/api/my-kpis/submissions', ['items' => $items, 'submit' => true])
            ->assertOk();

        return now()->toDateString();
    }

    public function test_login_membedakan_email_dan_kata_sandi_salah(): void
    {
        $this->postJson('/api/login', ['email' => 'tidakada@rotiwonosari.test', 'password' => 'password'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonMissingValidationErrors(['password']);

        $this->postJson('/api/login', ['email' => 'frontliner@rotiwonosari.test', 'password' => 'salah'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonMissingValidationErrors(['email']);

        $res = $this->postJson('/api/login', ['email' => 'frontliner@rotiwonosari.test', 'password' => 'password'])
            ->assertOk();
        $this->assertNotEmpty($res->json('user.created_at'));

        $this->api($res->json('token'))->getJson('/api/me')
            ->assertOk()
            ->assertJsonStructure(['id', 'email', 'created_at', 'is_active']);
    }

    public function test_akun_nonaktif_tidak_bisa_login(): void
    {
        User::where('email', 'driver@rotiwonosari.test')->update(['is_active' => false]);

        $this->postJson('/api/login', ['email' => 'driver@rotiwonosari.test', 'password' => 'password'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_dashboard_me_untuk_semua_role(): void
    {
        foreach (['frontliner', 'produksi', 'driver', 'sales', 'spv', 'sm'] as $who) {
            $this->api($this->tokenFor("{$who}@rotiwonosari.test"))
                ->getJson('/api/dashboard/me')
                ->assertOk();
        }

        $this->api($this->tokenFor('sm@rotiwonosari.test'))->getJson('/api/dashboard/team')->assertOk();
    }

    public function test_alur_persetujuan_sm_lalu_final_admin(): void
    {
        $date = $this->submitDaily('frontliner@rotiwonosari.test');
        $employee = User::where('email', 'frontliner@rotiwonosari.test')->first();

        // Store Manager melihat & menyetujui laporan (sebelumnya error 500)
        $sm = $this->tokenFor('sm@rotiwonosari.test');
        $reports = $this->api($sm)->getJson('/api/reviews/reports')->assertOk()->json('data');
        $this->assertTrue(collect($reports)->contains(fn ($r) => $r['user']['id'] === $employee->id));

        $this->api($sm)->postJson("/api/reviews/reports/{$employee->id}/{$date}", ['decision' => 'APPROVED'])
            ->assertOk();

        // Admin: antrean persetujuan akhir berisi laporan itu
        $admin = $this->tokenFor('admin@rotiwonosari.test');
        $queue = $this->api($admin)->getJson('/api/admin/final-reviews')->assertOk()->json('data');
        $this->assertTrue(collect($queue)->contains(fn ($r) => $r['user']['id'] === $employee->id));

        $this->api($admin)->getJson("/api/admin/final-reviews/{$employee->id}/{$date}")->assertOk();

        $this->api($admin)->postJson("/api/admin/final-reviews/{$employee->id}/{$date}", [
            'decision' => 'APPROVED',
            'notes'    => 'Sudah sesuai.',
        ])->assertOk();

        // Persetujuan akhir benar-benar tersimpan (sebelumnya hilang karena $fillable)
        $this->assertSame(
            0,
            KpiReview::whereHas('kpiSubmission', fn ($q) => $q->where('user_id', $employee->id))
                ->whereNull('finalized_at')->count()
        );
        $queue = $this->api($admin)->getJson('/api/admin/final-reviews')->assertOk()->json('data');
        $this->assertFalse(collect($queue)->contains(fn ($r) => $r['user']['id'] === $employee->id));
    }

    public function test_penolakan_sm_menjadi_revisi_untuk_karyawan(): void
    {
        $date = $this->submitDaily('produksi@rotiwonosari.test');
        $employee = User::where('email', 'produksi@rotiwonosari.test')->first();
        $sm = $this->tokenFor('sm@rotiwonosari.test');

        $this->api($sm)->postJson("/api/reviews/reports/{$employee->id}/{$date}", ['decision' => 'REJECTED'])
            ->assertStatus(422); // catatan wajib

        $this->api($sm)->postJson("/api/reviews/reports/{$employee->id}/{$date}", [
            'decision' => 'REJECTED',
            'notes'    => 'Foto bukti kurang jelas.',
        ])->assertOk();

        $this->api($this->tokenFor('produksi@rotiwonosari.test'))
            ->getJson("/api/my-kpis/history/{$date}")
            ->assertOk();
    }

    public function test_admin_tolak_final_mengembalikan_ke_karyawan(): void
    {
        $date = $this->submitDaily('driver@rotiwonosari.test');
        $employee = User::where('email', 'driver@rotiwonosari.test')->first();

        $this->api($this->tokenFor('sm@rotiwonosari.test'))
            ->postJson("/api/reviews/reports/{$employee->id}/{$date}", ['decision' => 'APPROVED'])->assertOk();

        $this->api($this->tokenFor('admin@rotiwonosari.test'))
            ->postJson("/api/admin/final-reviews/{$employee->id}/{$date}", [
                'decision' => 'REJECTED',
                'notes'    => 'Data pengiriman tidak lengkap.',
            ])->assertOk();

        $this->assertSame(0, $employee->kpiSubmissions()->where('status', 'APPROVED')->count());
    }

    public function test_laporan_store_manager_diputuskan_admin(): void
    {
        $date = $this->submitDaily('sm@rotiwonosari.test');
        $sm = User::where('email', 'sm@rotiwonosari.test')->first();
        $admin = $this->tokenFor('admin@rotiwonosari.test');

        $queue = $this->api($admin)->getJson('/api/admin/final-reviews')->assertOk()->json('data');
        $this->assertTrue(collect($queue)->contains(fn ($r) => $r['user']['id'] === $sm->id), 'Laporan SM harus masuk antrean Admin');

        $this->api($admin)->postJson("/api/admin/final-reviews/{$sm->id}/{$date}", ['decision' => 'APPROVED'])
            ->assertOk();

        $this->assertSame(0, $sm->kpiSubmissions()->whereDate('report_date', $date)->where('status', '!=', 'APPROVED')->count());
        $this->assertSame(0, KpiReview::whereHas('kpiSubmission', fn ($q) => $q->where('user_id', $sm->id))->whereNull('finalized_at')->count());
    }

    public function test_produksi_tanpa_foto_ditolak_saat_kirim(): void
    {
        $token = $this->tokenFor('produksi@rotiwonosari.test');
        $kpis = $this->api($token)->getJson('/api/my-kpis')->assertOk()->json('data');
        $items = collect($kpis)->map(fn ($k) => ['position_kpi_id' => $k['id'], 'answer_yes_no' => true])->values()->all();

        $this->api($token)->postJson('/api/my-kpis/submissions', ['items' => $items, 'submit' => true])
            ->assertStatus(422);
    }

    public function test_admin_kelola_indikator_tanpa_kode(): void
    {
        $admin = $this->tokenFor('admin@rotiwonosari.test');

        $res = $this->api($admin)->postJson('/api/admin/kpi-indicators', [
            'category_id'      => KpiCategory::query()->value('id'),
            'name'             => 'Indikator Uji Otomatis',
            'assessment_type'  => 'YES_NO',
            'calculation_type' => 'HIGHER_IS_BETTER',
        ])->assertCreated();

        $this->assertNotEmpty($res->json('data.code'));
    }

    public function test_admin_kelola_akun(): void
    {
        $admin = $this->tokenFor('admin@rotiwonosari.test');
        $roles = $this->api($admin)->getJson('/api/admin/roles')->assertOk()->json('data');
        $karyawan = collect($roles)->firstWhere('name', 'Karyawan')['id'];

        $id = $this->api($admin)->postJson('/api/admin/users', [
            'name'        => 'Karyawan Baru',
            'email'       => 'baru@rotiwonosari.test',
            'password'    => 'password123',
            'role_id'     => $karyawan,
            'position_id' => Position::where('name', 'Frontliner')->value('id'),
            'is_active'   => true,
        ])->assertCreated()->json('data.id');

        $this->api($admin)->putJson("/api/admin/users/{$id}", [
            'name'  => 'Karyawan Baru Diubah',
            'email' => 'baru2@rotiwonosari.test',
        ])->assertOk();

        $this->api($admin)->patchJson("/api/admin/users/{$id}/status", ['is_active' => false])->assertOk();

        $this->postJson('/api/login', ['email' => 'baru2@rotiwonosari.test', 'password' => 'password123'])
            ->assertStatus(422);
    }
}
