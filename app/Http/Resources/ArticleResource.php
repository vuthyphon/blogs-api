<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
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
            'thumbnail'=>$this->image,
            'category'=>[
                'id'=>$this->category->id,
                'name'=>$this->category->name,
                'name_kh'=>$this->category->name_kh
            ],
            'tags' => $this->tags->map(function ($tag) {
                return [
                    'value' => $tag->id,
                    'label' => $tag->name,
                    // 'slug' => $tag->slug,
                ];
            }),
            'author' => [
                'id' => $this->author->id ?? null,
                'name' => $this->author->name ?? null,
            ],
            'images' => $this->images->map(function ($img) {
                return [
                    'id' => $img->id,
                    'image_path' => $img->image_path,
                    // 'slug' => $tag->slug,
                ];
            }),

            'created_at' => $this->created_at,
        ];
    }
}
