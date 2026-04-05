<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\HRM\Contracts\NotificationRepositoryInterface;

class NotificationController extends Controller
{
    protected $repository;

    public function __construct(NotificationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        $notifications = $this->repository->getAll();
        return response()->json($notifications);
    }

    public function remove($id)
    {
        $removed = $this->repository->remove($id);
        if ($removed) {
            return response()->json(['success' => true, 'message' => 'Notification removed successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'Notification not found.'], 404);
    }

    /**
     * Mark notifications as read.
     */
    public function markRead(Request $request)
    {
        $ids = $request->input('ids', []);
        $this->repository->markRead($ids);
        return response()->json(['success' => true, 'message' => 'Notifications marked as read.']);
    }

    /**
     * Mark notifications as unread.
     */
    public function markUnread(Request $request)
    {
        $ids = $request->input('ids', []);
        $this->repository->markUnread($ids);
        return response()->json(['success' => true, 'message' => 'Notifications marked as unread.']);
    }
}
