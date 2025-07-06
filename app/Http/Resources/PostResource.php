<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'tags' => $this->tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    // 'slug' => $tag->slug,
                ];
            }),
            'author' => [
                'id' => $this->author->id ?? null,
                'name' => $this->author->name ?? null,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
