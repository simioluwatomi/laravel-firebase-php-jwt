<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => isset($this->email_verified_at) ? (string) $this->email_verified_at->timestamp : null,
            'created_at' => isset($this->created_at) ? (string) $this->created_at->timestamp : null,
            'updated_at' => isset($this->updated_at) ? (string) $this->updated_at->timestamp : null,
        ];
    }
}
