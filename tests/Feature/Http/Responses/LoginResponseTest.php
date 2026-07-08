<?php

namespace Tests\Feature\Http\Responses;

use App\Http\Responses\LoginResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class LoginResponseTest extends TestCase
{
    public function test_json_request_receives_two_factor_false_payload(): void
    {
        $request = Request::create('/login', 'POST');
        $request->headers->set('Accept', 'application/json');

        $response = (new LoginResponse())->toResponse($request);

        $this->assertSame(['two_factor' => false], $response->getData(true));
    }

    public function test_inertia_request_gets_a_location_response_for_the_intended_url(): void
    {
        app('session.store')->put('url.intended', 'https://lightbulb.test/admin');

        $request = Request::create('/login', 'POST');
        $request->headers->set('X-Inertia', 'true');
        $request->setLaravelSession(app('session.store'));
        $this->app->instance('request', $request);

        $response = (new LoginResponse())->toResponse($request);

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('https://lightbulb.test/admin', $response->headers->get('X-Inertia-Location'));
    }

    public function test_non_inertia_request_gets_a_plain_redirect_to_the_intended_url(): void
    {
        app('session.store')->put('url.intended', 'https://lightbulb.test/admin');

        $request = Request::create('/login', 'POST');
        $request->setLaravelSession(app('session.store'));
        $this->app->instance('request', $request);

        $response = (new LoginResponse())->toResponse($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://lightbulb.test/admin', $response->headers->get('Location'));
    }
}
