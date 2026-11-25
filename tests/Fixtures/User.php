<?php

namespace TakahiroNago\AppFoundation\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use TakahiroNago\AppFoundation\Contracts\Administrator;

class User extends Authenticatable implements Administrator
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_admin' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    public function grantAdminAccess(): void
    {
        $this->forceFill(['is_admin' => true])->save();
    }
}
