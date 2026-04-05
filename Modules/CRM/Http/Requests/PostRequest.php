<?php

namespace Modules\CRM\Http\Requests;

use Illuminate\Support\Str;
use Modules\Core\Rules\SlugRegexRule;
use Modules\Core\Rules\FileOrPathRule;
use Modules\CRM\Http\Requests\BaseFormRequest;

class PostRequest extends BaseFormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('post.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('post.update');
        }
        return $user && $user->can('post.read');
    }

    protected function prepareForValidation()
    {
        parent::prepareForValidation();

        $this->merge([
            'author_id' => auth()->id(),
            'author_name' => auth()->user() ? auth()->user()->name : null,
            'author_profile_image' => auth()->user() ? auth()->user()->avatar : null,
            'author_designation' => auth()->user() ? optional(optional(auth()->user()->employee)->designation)->title : null,
            'slug' => Str::slug($this->title ?? ''),
        ]);

        if (isset($this->status) && is_string($this->status)) {
            $this->merge([
                'status' => in_array($this->status, ["publish", "draft", "inactive"]) ? $this->status : 'draft',
            ]);
        }

        if (!isset($this->post_type) || !is_string($this->post_type)) {
            $this->merge(['post_type' => 'news']);
        }
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                new SlugRegexRule(),
                'unique:crm.posts,slug,' . $this->route('post', 0)
            ],
            'subtitle' => 'nullable|string|max:255',
            'header_image' => [
                'nullable',
                new FileOrPathRule(['jpeg', 'png', 'jpg', 'webp', 'svg']),
                'max:10000'
            ],
            'thumbnail' => [
                'nullable',
                new FileOrPathRule(['jpeg', 'png', 'jpg', 'webp', 'svg']),
                'max:5000'
            ],
            'additional_images' => 'nullable|array',
            'additional_images.*.temp_url' => 'required|string',
            'additional_images.*.file' => [
                'required',
                new FileOrPathRule(['jpeg', 'png', 'jpg', 'webp', 'svg']),
                'max:10000'
            ],
            'content' => 'required|string',
            'author_id' => 'required|exists:users,id',
            'author_name' => 'nullable|string|max:255',
            'author_profile_image' => 'nullable|string|max:255',
            'author_designation' => 'nullable|string|max:255',
            'read_time' => 'nullable|string|max:255',
            'status' => 'required|in:publish,draft,inactive',
            'post_type' => 'required|in:news,press_release',
            'press_release_link' => 'nullable|url|max:255',
            'publish_date' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'integer|exists:crm.post_tags,id',
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:crm.post_categories,id',
        ];
    }
}
