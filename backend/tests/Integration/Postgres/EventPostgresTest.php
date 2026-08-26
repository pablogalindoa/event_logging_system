<?php

use App\Enums\EventLevel;
use App\Models\Event;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('This test requires PostgreSQL.');
    }
});

it('persists posted context as jsonb and normalizes timestamptz to UTC with milliseconds', function () {
    $this->postJson('/events', [
        'level' => 'error',
        'message' => 'PostgreSQL integration event',
        'source' => 'integration-test',
        'context' => ['trace_id' => 'trace-123', 'attempt' => 2],
        'occurred_at' => '2026-08-25T18:42:13.381-06:00',
    ])
        ->assertCreated()
        ->assertJsonPath('data.occurred_at', '2026-08-26T00:42:13.381Z');

    $stored = DB::table('events')
        ->selectRaw("pg_typeof(context)::text AS context_type, context->>'trace_id' AS trace_id")
        ->selectRaw("to_char(occurred_at AT TIME ZONE 'UTC', 'YYYY-MM-DD\"T\"HH24:MI:SS.MS\"Z\"') AS occurred_at_utc")
        ->first();

    expect($stored->context_type)->toBe('jsonb')
        ->and($stored->trace_id)->toBe('trace-123')
        ->and($stored->occurred_at_utc)->toBe('2026-08-26T00:42:13.381Z');
});

it('enforces PostgreSQL check constraints', function (array $attributes, string $constraint) {
    expect(fn () => DB::table('events')->insert(array_merge([
        'level' => EventLevel::Info->value,
        'message' => 'Valid message',
        'context' => json_encode(['valid' => true], JSON_THROW_ON_ERROR),
        'occurred_at' => now(),
        'created_at' => now(),
    ], $attributes)))->toThrow(QueryException::class, $constraint);
})->with([
    'level' => [['level' => 'fatal'], 'events_level_check'],
    'message length' => [['message' => str_repeat('x', 2001)], 'events_message_length_check'],
    'context shape' => [['context' => json_encode(['list-item'], JSON_THROW_ON_ERROR)], 'events_context_object_check'],
    'context size' => [['context' => json_encode(['payload' => str_repeat('x', 65536)], JSON_THROW_ON_ERROR)], 'events_context_size_check'],
]);

it('supports filtering pagination and deterministic event ordering', function () {
    $sharedTimestamp = '2026-08-25T12:00:00.123Z';
    Event::factory()->create(['level' => EventLevel::Info, 'message' => 'Outside range', 'occurred_at' => '2026-08-25T09:00:00Z']);
    $first = Event::factory()->create(['level' => EventLevel::Error, 'message' => 'First equal event', 'occurred_at' => $sharedTimestamp]);
    $second = Event::factory()->create(['level' => EventLevel::Error, 'message' => 'Second equal event', 'occurred_at' => $sharedTimestamp]);
    Event::factory()->create(['level' => EventLevel::Error, 'message' => 'Later event', 'occurred_at' => '2026-08-25T13:00:00Z']);

    $response = $this->getJson('/events?level=error&from=2026-08-25T12:00:00Z&to=2026-08-25T13:00:00Z&per_page=2&page=1');

    $response
        ->assertOk()
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonPath('data.0.message', 'Later event')
        ->assertJsonPath('data.1.id', $second->id);

    $this->getJson('/events?level=error&from=2026-08-25T12:00:00Z&to=2026-08-25T13:00:00Z&per_page=2&page=2')
        ->assertOk()
        ->assertJsonPath('data.0.id', $first->id);
});

it('creates the indexes used by listing queries', function () {
    $indexes = DB::table('pg_indexes')
        ->where('schemaname', 'public')
        ->where('tablename', 'events')
        ->pluck('indexname');

    expect($indexes)->toContain(
        'events_pkey',
        'events_occurred_at_id_idx',
        'events_level_occurred_at_id_idx',
    );
});
