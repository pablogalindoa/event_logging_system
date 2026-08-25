<?php

use App\Enums\EventLevel;
use App\Models\Event;

function validEventPayload(array $overrides = []): array
{
    return array_merge([
        'level' => 'error',
        'message' => 'Payment authorization failed',
        'source' => 'checkout-api',
        'context' => ['order_id' => 'ord_123'],
        'occurred_at' => '2026-08-25T18:42:13.381-06:00',
    ], $overrides);
}

it('creates an event and returns the agreed resource', function () {
    $response = $this->postJson('/events', validEventPayload());

    $response
        ->assertCreated()
        ->assertHeader('X-Request-ID')
        ->assertJsonPath('data.level', 'error')
        ->assertJsonPath('data.message', 'Payment authorization failed')
        ->assertJsonPath('data.source', 'checkout-api')
        ->assertJsonPath('data.context.order_id', 'ord_123')
        ->assertJsonPath('data.occurred_at', '2026-08-26T00:42:13.381Z')
        ->assertJsonStructure(['data' => ['id', 'level', 'message', 'source', 'context', 'occurred_at', 'created_at']]);

    $event = Event::query()->sole();

    expect($event->level)->toBe(EventLevel::Error)
        ->and($event->context)->toBe(['order_id' => 'ord_123']);
});

it('accepts every supported event level', function (EventLevel $level) {
    $this->postJson('/events', validEventPayload(['level' => $level->value]))
        ->assertCreated();
})->with(EventLevel::cases());

it('accepts omitted nullable fields and returns a stable shape', function () {
    $payload = validEventPayload();
    unset($payload['source'], $payload['context']);

    $this->postJson('/events', $payload)
        ->assertCreated()
        ->assertJsonPath('data.source', null)
        ->assertJsonPath('data.context', null);
});

it('converts an empty source to null and trims the message', function () {
    $this->postJson('/events', validEventPayload([
        'message' => '  concise message  ',
        'source' => '',
    ]))->assertCreated();

    $event = Event::query()->sole();

    expect($event->message)->toBe('concise message')
        ->and($event->source)->toBeNull();
});

it('rejects missing required fields with the standard error shape', function (string $field) {
    $payload = validEventPayload();
    unset($payload[$field]);

    $this->postJson('/events', $payload)
        ->assertUnprocessable()
        ->assertHeader('X-Request-ID')
        ->assertJsonPath('error.code', 'VALIDATION_FAILED')
        ->assertJsonPath('error.message', 'The request contains invalid data.')
        ->assertJsonStructure(['error' => ['details' => [$field], 'request_id']]);
})->with(['level', 'message', 'occurred_at']);

it('rejects an unsupported level', function () {
    $this->postJson('/events', validEventPayload(['level' => 'fatal']))
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_FAILED')
        ->assertJsonStructure(['error' => ['details' => ['level']]]);
});

it('enforces message and source character limits', function (array $overrides, string $field) {
    $this->postJson('/events', validEventPayload($overrides))
        ->assertUnprocessable()
        ->assertJsonStructure(['error' => ['details' => [$field]]]);
})->with([
    'empty message' => [['message' => '   '], 'message'],
    'long message' => [['message' => str_repeat('m', 2001)], 'message'],
    'long source' => [['source' => str_repeat('s', 256)], 'source'],
]);

it('requires an ISO 8601 occurred_at value with an explicit timezone', function (string $timestamp) {
    $this->postJson('/events', validEventPayload(['occurred_at' => $timestamp]))
        ->assertUnprocessable()
        ->assertJsonStructure(['error' => ['details' => ['occurred_at']]]);
})->with([
    '2026-08-25 18:42:13',
    '2026-08-25T18:42:13',
    'not-a-date',
]);

it('rejects unknown top-level fields', function () {
    $this->postJson('/events', validEventPayload(['unexpected' => true]))
        ->assertUnprocessable()
        ->assertJsonStructure(['error' => ['details' => ['unexpected']]]);
});

it('rejects context values that are not JSON objects', function (mixed $context) {
    $this->postJson('/events', validEventPayload(['context' => $context]))
        ->assertUnprocessable()
        ->assertJsonStructure(['error' => ['details' => ['context']]]);
})->with([
    'array' => [['one', 'two']],
    'string' => ['diagnostic'],
    'number' => [42],
]);

it('enforces the encoded context size limit', function () {
    $this->postJson('/events', validEventPayload([
        'context' => ['diagnostic' => str_repeat('x', 65536)],
    ]))
        ->assertUnprocessable()
        ->assertJsonStructure(['error' => ['details' => ['context']]]);
});

it('rejects request bodies larger than 128 KiB', function () {
    $payload = json_encode(validEventPayload([
        'context' => ['diagnostic' => str_repeat('x', 132000)],
    ]), JSON_THROW_ON_ERROR);

    $this->call('POST', '/events', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], $payload)
        ->assertStatus(413)
        ->assertJsonPath('error.code', 'PAYLOAD_TOO_LARGE');
});

it('rejects malformed JSON', function () {
    $this->call('POST', '/events', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], '{"level":')
        ->assertBadRequest()
        ->assertJsonPath('error.code', 'INVALID_JSON');
});

it('requires the JSON content type', function () {
    $this->call('POST', '/events', [], [], [], [
        'CONTENT_TYPE' => 'text/plain',
    ], json_encode(validEventPayload(), JSON_THROW_ON_ERROR))
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_FAILED');
});
