<?php

namespace App\Http\Resources;

use App\Models\Command;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Command */
class CommandResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->site->server_id,
            'site_id' => $this->site_id,
            'name' => $this->name,
            'command' => $this->command,
            'variables' => $this->getVariables(),
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
