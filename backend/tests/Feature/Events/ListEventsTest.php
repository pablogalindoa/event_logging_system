<?php

use App\Enums\EventLevel;
use App\Models\Event;

function createEventAt(string $occurredAt, array $attributes = []): Event
{
    return Event::factory()->create(array_merge([
        'occurred_at' => $occurredAt,
    ], $attributes));
}

it('lists events newest first with the default pagination', function () {
    createEventAt('2026-08-25T10:00:00Z', ['message' => 'Oldest']);
    createEventAt('2026-08-25T12:00:00Z', ['message' => 'Newest']);
    createEventAt('2026-08-25T11:00:00Z', ['message' => 'Middle']);

    $response = $this->getJson('/events');

    $response
        ->assertOk()
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.per_page', 25)
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.from', 1)
        ->assertJsonPath('meta.to', 3)
        ->assertJsonPath('data.0.message', 'Newest')
        ->assertJsonPath('data.1.message', 'Middle')
        ->assertJsonPath('data.2.message', 'Oldest')
        ->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'per_page', 'last_page', 'total', 'from', 'to'],
            'links' => ['first', 'last', 'previous', 'next'],
        ]);
});

it('paginates events and preserves query parameters in links', function () {
    Event::factory()->count(5)->sequence(
        fn ($sequence) => ['occurred_at' => "2026-08-25T10:0{$sequence->index}:00Z"]
    )->create();

    $this->getJson('/events?per_page=2&page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.last_page', 3)
        ->assertJsonPath('meta.total', 5)
        ->assertJsonPath('meta.from', 3)
        ->assertJsonPath('meta.to', 4)
        ->assertJsonPath('links.previous', fn (string $url) => str_contains($url, 'page=1') && str_contains($url, 'per_page=2'))
        ->assertJsonPath('links.next', fn (string $url) => str_contains($url, 'page=3') && str_contains($url, 'per_page=2'));
});

it('validates per_page limits', function (string $perPage) {
    $this->getJson("/events?per_page={$perPage}")
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_FAILED')
        ->assertJsonStructure(['error' => ['details' => ['per_page'], 'request_id']]);
})->with(['0', '101', 'not-an-integer']);

it('filters events by level', function () {
    createEventAt('2026-08-25T10:00:00Z', ['level' => EventLevel::Error]);
    createEventAt('2026-08-25T11:00:00Z', ['level' => EventLevel::Info]);
    createEventAt('2026-08-25T12:00:00Z', ['level' => EventLevel::Error]);

    $this->getJson('/events?level=error')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0.level', 'error')
        ->assertJsonPath('data.1.level', 'error');
});

it('filters inclusively from a timestamp', function () {
    createEventAt('2026-08-25T09:59:59Z');
    createEventAt('2026-08-25T10:00:00Z');
    createEventAt('2026-08-25T10:00:01Z');

    $this->getJson('/events?from=2026-08-25T10:00:00Z')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2);
});

it('filters inclusively to a timestamp', function () {
    createEventAt('2026-08-25T09:59:59Z');
    createEventAt('2026-08-25T10:00:00Z');
    createEventAt('2026-08-25T10:00:01Z');

    $this->getJson('/events?to=2026-08-25T10:00:00Z')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2);
});

it('filters by an inclusive date range', function () {
    createEventAt('2026-08-25T09:59:59Z');
    createEventAt('2026-08-25T10:00:00Z');
    createEventAt('2026-08-25T11:00:00Z');
    createEventAt('2026-08-25T11:00:01Z');

    $this->getJson('/events?from=2026-08-25T10:00:00Z&to=2026-08-25T11:00:00Z')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2);
});

it('rejects an inverted date range', function () {
    $this->getJson('/events?from=2026-08-25T11:00:00Z&to=2026-08-25T10:00:00Z')
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_FAILED')
        ->assertJsonStructure(['error' => ['details' => ['to'], 'request_id']]);
});

it('rejects date filters without an explicit timezone', function (string $parameter) {
    $this->getJson("/events?{$parameter}=2026-08-25T10:00:00")
        ->assertUnprocessable()
        ->assertJsonStructure(['error' => ['details' => [$parameter]]]);
})->with(['from', 'to']);

it('orders equal timestamps by descending id', function () {
    $first = createEventAt('2026-08-25T10:00:00Z');
    $second = createEventAt('2026-08-25T10:00:00Z');
    $third = createEventAt('2026-08-25T10:00:00Z');

    $this->getJson('/events')
        ->assertOk()
        ->assertJsonPath('data.0.id', $third->id)
        ->assertJsonPath('data.1.id', $second->id)
        ->assertJsonPath('data.2.id', $first->id);
});

it('returns an empty paginated result', function () {
    $this->getJson('/events')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0)
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.last_page', 1)
        ->assertJsonPath('meta.from', null)
        ->assertJsonPath('meta.to', null)
        ->assertJsonPath('links.previous', null)
        ->assertJsonPath('links.next', null);
});
