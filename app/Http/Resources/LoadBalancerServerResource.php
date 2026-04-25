<?php

namespace App\Http\Resources;

use App\Models\LoadBalancerServer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LoadBalancerServer */
class LoadBalancerServerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'load_balancer_id' => $this->load_balancer_id,
            'ip' => $this->ip,
            'port' => $this->port,
            'weight' => $this->weight,
            'backup' => $this->backup,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
