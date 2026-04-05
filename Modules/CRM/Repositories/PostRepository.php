<?php

namespace Modules\CRM\Repositories;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\CRM\Models\Post;
use Modules\CRM\Traits\File\FileManager;
use Modules\CRM\Contracts\PostRepositoryInterface;

class PostRepository implements PostRepositoryInterface
{
    use FileManager;

    protected $model;

    public function __construct(Post $model)
    {
        $this->model = $model;
    }

    public function paginate(array $filters = [])
    {
        return $this->model
            ->with(['tags', 'categories', 'author'])
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->whereAny(['title', 'subtitle', 'content'], 'ilike', "%{$filters['q']}%");
            })
            ->when(isset($filters['status']), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->orderBy(
                data_get($filters, 'sort_by', 'id'),
                data_get($filters, 'order_by', 'desc')
            )
            ->paginate($filters['per_page'] ?? 10);
    }

    public function find(int $id)
    {
        return $this->model->with(['tags', 'categories', 'author'])->findOrFail($id);
    }

    public function create(array $data)
    {
        if (isset($data['header_image'])) {
            $fileName = $data['header_image']->getClientOriginalName();
            $data['header_image'] = $this->uploadFile(
                $data['header_image'],
                "posts/" . Str::slug(Str::substr($data['title'], 0, 30)),
                $fileName
            );
        }

        if (isset($data['thumbnail'])) {
            $fileName = $data['thumbnail']->getClientOriginalName();
            $data['thumbnail'] = $this->uploadFile(
                $data['thumbnail'],
                "posts/" . Str::slug(Str::substr($data['title'], 0, 30)) . "/thumbnails",
                $fileName
            );
        }

        if (isset($data['additional_images'])) {

            foreach ($data['additional_images'] as $image) {
                if (empty($image['file']) || empty($image['temp_url']) || !Str::contains($data['content'], $image['temp_url'])) continue;

                $fileName = $image['file']->getClientOriginalName();
                $uploadedPath = $this->uploadFile(
                    $image['file'],
                    "posts/" . Str::slug(Str::substr($data['title'], 0, 30)) . "/additional_images",
                    $fileName
                );

                $data['content'] = Str::replace(
                    $image['temp_url'],
                    Storage::disk('s3')->url($uploadedPath),
                    $data['content']
                );
            };
        }

        $post = $this->model->create($data);

        if (isset($data['tags'])) {
            $post->tags()->sync($data['tags']);
        }

        if (isset($data['categories'])) {
            $post->categories()->sync($data['categories']);
        }

        return $post;
    }

    public function update(int $id, array $data)
    {
        $post = $this->model->findOrFail($id);

        if (isset($data['header_image']) && !is_string($data['header_image'])) {

            if (!empty($post->header_image)) $this->deleteFile($post->header_image);

            $fileName = $data['header_image']->getClientOriginalName();
            $data['header_image'] = $this->uploadFile(
                $data['header_image'],
                "posts/" . Str::slug(Str::substr($data['title'], 0, 30)),
                $fileName
            );
        } else {
            unset($data['header_image']);
        }

        if (isset($data['thumbnail']) && !is_string($data['thumbnail'])) {

            if (!empty($post->thumbnail)) $this->deleteFile($post->thumbnail);

            $fileName = $data['thumbnail']->getClientOriginalName();
            $data['thumbnail'] = $this->uploadFile(
                $data['thumbnail'],
                "posts/" . Str::slug(Str::substr($data['title'], 0, 30)) . "/thumbnails",
                $fileName
            );
        } else {
            unset($data['thumbnail']);
        }

        if (isset($data['additional_images'])) {

            foreach ($data['additional_images'] as $image) {
                if (empty($image['file']) || empty($image['temp_url']) || !Str::contains($data['content'], $image['temp_url'])) continue;

                $fileName = $image['file']->getClientOriginalName();
                $uploadedPath = $this->uploadFile(
                    $image['file'],
                    "posts/" . Str::slug(Str::substr($data['title'], 0, 30)) . "/additional_images",
                    $fileName
                );

                $data['content'] = Str::replace(
                    $image['temp_url'],
                    Storage::disk('s3')->url($uploadedPath),
                    $data['content']
                );
            };
        }

        $post->update($data);

        $post->tags()->sync($data['tags'] ?? []);
        $post->categories()->sync($data['categories'] ?? []);

        return $post;
    }

    public function delete(int $id)
    {
        $post = $this->model->findOrFail($id);

        if (!empty($post->header_image)) $this->deleteFile($post->header_image);
        if (!empty($post->thumbnail)) $this->deleteFile($post->thumbnail);

        $post->delete();
    }
}
