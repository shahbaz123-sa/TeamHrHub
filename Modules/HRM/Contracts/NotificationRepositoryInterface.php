<?php

namespace Modules\HRM\Contracts;

interface NotificationRepositoryInterface
{
    public function getAll();
    public function remove($id);
    public function markRead(array $ids);
    public function markUnread(array $ids);
}

