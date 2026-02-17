<?php

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake();
    $this->user = User::factory()->create(['email' => 'kevininc155@gmail.com']);
});

// --- check ---

it('returns version info when latest.json exists', function () {
    Storage::put('releases/latest.json', json_encode([
        'version' => '1.2.0',
        'version_code' => 5,
        'changelog' => 'Nouvelles fonctionnalités',
        'filename' => 'cocoon-1.2.0.apk',
        'released_at' => '2026-02-17T12:00:00Z',
    ]));

    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/app/version');

    $response->assertSuccessful()
        ->assertJsonStructure(['version', 'version_code', 'changelog', 'download_url'])
        ->assertJsonPath('version', '1.2.0')
        ->assertJsonPath('version_code', 5)
        ->assertJsonPath('changelog', 'Nouvelles fonctionnalités');

    expect($response->json('download_url'))->toContain('/api/app/download');
});

it('returns 401 without a valid token', function () {
    $this->getJson('/api/app/version')->assertUnauthorized();
});

it('returns 404 when no release exists', function () {
    Sanctum::actingAs($this->user);

    $this->getJson('/api/app/version')->assertNotFound();
});

// --- download ---

it('streams the APK with a valid signed URL', function () {
    Storage::put('releases/cocoon-1.2.0.apk', 'fake-apk-content');

    $url = URL::temporarySignedRoute(
        'api.app.download',
        now()->addHour(),
        ['filename' => 'cocoon-1.2.0.apk'],
    );

    $response = $this->get($url);

    $response->assertSuccessful()
        ->assertHeader('Content-Disposition', 'attachment; filename=cocoon-1.2.0.apk');
});

it('returns 403 without a valid signature', function () {
    $this->get('/api/app/download?filename=cocoon-1.2.0.apk')->assertForbidden();
});

it('returns 404 if APK file is missing for download', function () {
    $url = URL::temporarySignedRoute(
        'api.app.download',
        now()->addHour(),
        ['filename' => 'missing.apk'],
    );

    $this->get($url)->assertNotFound();
});

// --- app:publish-release command ---

it('publishes a release and creates latest.json', function () {
    $apkPath = tempnam(sys_get_temp_dir(), 'cocoon').'.apk';
    file_put_contents($apkPath, 'fake-apk-content');

    $this->artisan('app:publish-release', [
        'apk_path' => $apkPath,
        '--changelog' => 'Version de test',
    ])->assertSuccessful();

    Storage::assertExists('releases/latest.json');

    $release = json_decode(Storage::get('releases/latest.json'), true);
    expect($release['changelog'])->toBe('Version de test');
    expect($release['version_code'])->toBe((int) config('nativephp.version_code'));

    Storage::assertExists('releases/'.$release['filename']);

    unlink($apkPath);
});

it('fails if the APK source file does not exist', function () {
    $this->artisan('app:publish-release', [
        'apk_path' => '/nonexistent/path/app.apk',
    ])->assertFailed();
});
