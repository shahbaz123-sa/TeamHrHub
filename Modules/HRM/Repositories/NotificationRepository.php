<?php

namespace Modules\HRM\Repositories;

use App\Models\Notification;
use Modules\HRM\Contracts\NotificationRepositoryInterface;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function getAll()
    {
        $userId = auth()->id();
        return Notification::with('employee', 'sender')
            ->where('receiver_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function remove($id)
    {
        $notification = Notification::find($id);
        if ($notification) {
            $notification->delete();
            return true;
        }
        return false;
    }

    public function markRead(array $ids)
    {
        $userId = auth()->id();
        return Notification::where('receiver_id', $userId)->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function markUnread(array $ids)
    {
        $userId = auth()->id();
        return Notification::where('receiver_id', $userId)->whereNotNull('read_at')->update(['read_at' => null]);
    }
}

