<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class EventController extends Controller
{
    public function store(StoreEventRequest $request): JsonResponse
    {
        $attributes = $request->validated();
        $attributes['occurred_at'] = Carbon::parse($attributes['occurred_at'])->utc();

        $event = Event::query()->create($attributes);

        return (new EventResource($event))
            ->response()
            ->setStatusCode(201);
    }
}
