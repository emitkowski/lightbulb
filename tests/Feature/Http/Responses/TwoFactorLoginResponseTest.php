<?php

namespace Tests\Feature\Http\Responses;

use App\Http\Responses\TwoFactorLoginResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class TwoFactorLoginResponseTest extends TestCase
{
    public function test_json_request_receives_empty_204_response(): void
    {
        $request = Request::create('/two-factor-challenge', 'POST');
        $request->headers->set('Accept', 'application/json');

        $response = (new TwoFactorLoginResponse())->toResponse($request);

        $this->assertSame(204, $response->getStatusCode());
    }

    public function test_inertia_request_gets_a_location_response_for_the_intended_url(): void
    {
        app('session.store')->put('url.intended', 'https://lightbulb.test/admin');

        $request = Request::create('/two-factor-challenge', 'POST');
        $request->headers->set('X-Inertia', 'true');
        $request->setLaravelSession(app('session.store'));
        $this->app->instance('request', $request);

        $response = (new TwoFactorLoginResponse())->toResponse($request);

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('https://lightbulb.test/admin', $response->headers->get('X-Inertia-Location'));
    }

    public function test_non_inertia_request_gets_a_plain_redirect_to_the_intended_url(): void
    {
        app('session.store')->put('url.intended', 'https://lightbulb.test/admin');

        $request = Request::create('/two-factor-challenge', 'POST');
        $request->setLaravelSession(app('session.store'));
        $this->app->instance('request', $request);

        $response = (new TwoFactorLoginResponse())->toResponse($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://lightbulb.test/admin', $response->headers->get('Location'));
    }
}
