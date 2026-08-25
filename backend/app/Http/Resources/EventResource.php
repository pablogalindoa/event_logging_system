<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'level' => $this->level->value,
            'message' => $this->message,
            'source' => $this->source,
            'context' => $this->context,
            'occurred_at' => $this->occurred_at->utc()->format('Y-m-d\TH:i:s.v\Z'),
            'created_at' => $this->created_at->utc()->format('Y-m-d\TH:i:s.v\Z'),
        ];
    }
}
