<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListEventsRequest;
use App\Http\Requests\StoreEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class EventController extends Controller
{
    public function index(ListEventsRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $events = Event::query()
            ->when($filters['level'] ?? null, fn ($query, string $level) => $query->where('level', $level))
            ->when($filters['from'] ?? null, fn ($query, string $from) => $query->where('occurred_at', '>=', $this->databaseTimestamp($from)))
            ->when($filters['to'] ?? null, fn ($query, string $to) => $query->where('occurred_at', '<=', $this->databaseTimestamp($to)))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 25)
            ->withQueryString();

        return response()->json([
            'data' => EventResource::collection($events->items())->resolve($request),
            'meta' => [
                'current_page' => $events->currentPage(),
                'per_page' => $events->perPage(),
                'last_page' => $events->lastPage(),
                'total' => $events->total(),
                'from' => $events->firstItem(),
                'to' => $events->lastItem(),
            ],
            'links' => [
                'first' => $events->url(1),
                'last' => $events->url($events->lastPage()),
                'previous' => $events->previousPageUrl(),
                'next' => $events->nextPageUrl(),
            ],
        ]);
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $attributes = $request->validated();
        $attributes['occurred_at'] = Carbon::parse($attributes['occurred_at'])->utc();

        $event = Event::query()->create($attributes);

        return (new EventResource($event))
            ->response()
            ->setStatusCode(201);
    }

    private function databaseTimestamp(string $timestamp): string
    {
        return Carbon::parse($timestamp)->utc()->format(Event::DATE_FORMAT);
    }
}
