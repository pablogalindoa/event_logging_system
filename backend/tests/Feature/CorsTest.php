<?php

it('allows a configured frontend origin to read events', function () {
    config()->set('cors.allowed_origins', ['https://dashboard.example.com']);

    $this->getJson('/events', ['Origin' => 'https://dashboard.example.com'])
        ->assertOk()
        ->assertHeader('Access-Control-Allow-Origin', 'https://dashboard.example.com')
        ->assertHeader('Access-Control-Expose-Headers', 'X-Request-ID');
});

it('does not echo an unconfigured frontend origin', function () {
    config()->set('cors.allowed_origins', ['https://dashboard.example.com']);

    $this->getJson('/events', ['Origin' => 'https://untrusted.example.com'])
        ->assertOk()
        ->assertHeader('Access-Control-Allow-Origin', 'https://dashboard.example.com');
});
