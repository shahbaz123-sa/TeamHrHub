<?php

namespace Modules\CRM\Http\Resources;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'subtitle' => $this->subtitle,
            'header_image' => $this->header_image ? Storage::disk('s3')->url($this->header_image) : '',
            'thumbnail' => $this->thumbnail ? Storage::disk('s3')->url($this->thumbnail) : '',
            'content' => $this->content,
            'author_id' => $this->author_id,
            'author_name' => $this->author_name,
            'author_profile_image' => $this->author_profile_image ? Storage::disk('s3')->url($this->author_profile_image) : '',
            'author_designation' => $this->author_designation,
            'read_time' => $this->read_time,
            'post_type' => $this->post_type,
            'press_release_link' => $this->press_release_link,
            'author' => $this->whenLoaded('author', new UserResource($this->author)),
            'status' => $this->status,
            'tags' => $this->whenLoaded('tags', Post\TagResource::collection($this->tags)),
            'categories' => $this->whenLoaded('categories', Post\CategoryResource::collection($this->categories)),
            'publish_date' => $this->publish_date,
            'published_at' => $this->publish_date ? Carbon::parse($this->publish_date)->format('D, M d, Y') : Carbon::parse($this->created_at)->format('D, M d, Y'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
