<?php

namespace Modules\CRM\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmailSettingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'notify_on' => $this->notify_on,
            'slug' => $this->slug,
            'recipients' => $this->recipients,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
