<?php

namespace Modules\HRM\Traits\User;

trait RoleConditions
{
    public function onlyEmployee()
    {
        return $this->hasRole('Employee') && !$this->hasRole(['Hr', 'Manager']);
    }

    public function onlyManager()
    {
        return $this->hasRole('Manager') && !$this->hasRole('Hr');
    }
}
