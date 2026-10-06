<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FrontendGatewayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['backend.url' => 'https://api.example.test', 'backend.gateway_key' => 'gateway-test-key']);
        Http::preventStrayRequests();
    }

    public function test_forwards_html_and_both_session_cookies_without_accessing_a_database(): void
    {
        Http::fake(['api.example.test/*' => Http::response('<form>Login</form>', 200, ['Content-Type' => 'text/html', 'Set-Cookie' => ['vistiq_session=encrypted; Path=/; HttpOnly', 'XSRF-TOKEN=csrf; Path=/']])]);

        $response = $this->get('/login');

        $response->assertOk()->assertSee('<form>Login</form>', false);
        $this->assertCount(2, $response->headers->getCookies());
        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://api.example.test/login' && $request->hasHeader('X-Vistiq-Gateway-Key', 'gateway-test-key') && $request->hasHeader('X-Vistiq-Surface', config('backend.surface')));
        $this->assertSame([], config('database.connections'));
    }

    public function test_forwards_json_csrf_cookie_query_and_validation_status(): void
    {
        Http::fake(['api.example.test/*' => Http::response(['message' => 'Invalid email', 'errors' => ['email' => ['Invalid email']]], 422)]);

        $this->withHeaders(['Cookie' => 'vistiq_session=value', 'X-CSRF-TOKEN' => 'csrf-value'])
            ->postJson('/api/v1/login?return=home', ['email' => 'invalid', 'password' => 'Password123'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');

        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://api.example.test/api/v1/login?return=home' && $request['password'] === 'Password123' && $request->hasHeader('Cookie', 'vistiq_session=value') && $request->hasHeader('X-CSRF-TOKEN', 'csrf-value'));
    }

    public function test_preserves_redirects_and_download_headers(): void
    {
        Http::fakeSequence()->push('', 302, ['Location' => 'https://frontend.example.test/admin'])
            ->push('csv-content', 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename=questions.csv']);

        $this->get('/login')->assertRedirect('https://frontend.example.test/admin');
        $this->get('/admin/questions/export')->assertOk()->assertContent('csv-content')->assertHeader('Content-Disposition', 'attachment; filename=questions.csv');
        Http::assertSentCount(2);
    }

    public function test_forwards_multipart_files_and_nested_field_names(): void
    {
        Http::fake(['api.example.test/*' => Http::response('', 302, ['Location' => '/admin/media'])]);

        $this->post('/admin/media', ['title' => 'Map', 'criteria' => ['reading' => 7], 'files' => [UploadedFile::fake()->createWithContent('map.csv', 'name,value')]])->assertRedirect('/admin/media');

        Http::assertSent(function (ClientRequest $request): bool {
            $parts = $request->data();
            $names = array_column($parts, 'name');

            return in_array('criteria[reading]', $names, true) && in_array('files[0]', $names, true) && $request->hasFile('files[0]', null, 'map.csv');
        });
    }

    public function test_backend_connection_failure_returns_502(): void
    {
        Http::fake(['api.example.test/*' => Http::failedConnection()]);

        $this->getJson('/api/v1/session')->assertStatus(502)->assertJsonPath('message', 'Không kết nối được Vistiq.Api.');
    }
}
