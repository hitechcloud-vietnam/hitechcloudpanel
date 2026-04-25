<?php

namespace App\Http\Resources;

use App\Models\Script;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Script */
class ScriptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'name' => $this->name,
            'content' => $this->content,
            'variables' => $this->getVariables(),
            'last_execution' => ScriptExecutionResource::make($this->lastExecution),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
