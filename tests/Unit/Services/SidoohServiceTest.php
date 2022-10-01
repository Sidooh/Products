<?php

namespace Tests\Unit\Services;

use App\Services\SidoohService;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SidoohServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['SIDOOH_ACCOUNTS_API_URL', 'http://localhost:8000/api/v1']);
    }

    public function test_http()
    {
        Http::fake([
            'localhost:8000/api/v1/users/signin' => Http::sequence()
                ->push(['access_token' => 'test-token'])
                ->push(),
        ]);

        $pendingRequest = SidoohService::http();

        Http::assertSent(function(Request $request) {
            return $request->hasHeader('Content-Type', 'application/json') &&
                ! in_array('Authorization', $request->headers()) &&
                $request->url() == 'http://localhost:8000/api/v1/users/signin' &&
                $request['email'] == 'aa@a.a' &&
                $request['password'] == '12345678';
        });

        $pendingRequest->send('POST', 'localhost:8000/api/v1/users/signin');

        Http::assertSent(function(Request $request) {
            return $request->hasHeader('Authorization', 'Bearer test-token') &&
                $request->url() == 'http://localhost:8000/api/v1/users/signin';
        });
    }

    public function test_authentication()
    {
        Http::fake([
            'localhost:8000/api/v1/users/signin' => Http::sequence()
                ->push(['access_token' => 'test-token'])
                ->push(['result' => 0, 'message' => 'unauthenticated'], 401)
                ->pushStatus(400),
        ]);

        // Test Successful
        $response = SidoohService::authenticate();

        Http::assertSent(function(Request $request) {
            return $request->hasHeader('Content-Type', 'application/json') &&
                $request->url() == 'http://localhost:8000/api/v1/users/signin' &&
                $request['email'] == 'aa@a.a' &&
                $request['password'] == '12345678';
        });

        $this->assertEquals($response, 'test-token');

        // Test 401
        $this->assertThrows(fn() => SidoohService::authenticate(), RequestException::class);

//        $response = SidoohService::authenticate();

//        Http::assertSent(function (Request $request) {
//            return $request->hasHeader('Content-Type', 'application/json') &&
//                $request->url() == 'http://localhost:8000/api/v1/users/signin' &&
//                $request['email'] == 'aa@a.a' &&
//                $request['password'] == '12345678';
//        });

        // Test 400
        $this->assertThrows(fn() => SidoohService::authenticate(), RequestException::class);
    }
}
