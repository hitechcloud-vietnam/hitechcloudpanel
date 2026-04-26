<?php

namespace App\Http\Resources;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin File */
class FileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'user_id' => $this->user_id,
            'server_user' => $this->server_user,
            'path' => $this->path,
            'type' => $this->type,
            'name' => $this->name,
            'size' => $this->size,
            'links' => $this->links,
            'owner' => $this->owner,
            'group' => $this->group,
            'date' => $this->date,
            'permissions' => $this->permissions,
            'file_path' => $this->getFilePath(),
            'is_extractable' => $this->isExtractable(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
