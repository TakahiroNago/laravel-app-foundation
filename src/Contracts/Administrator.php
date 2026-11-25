<?php

namespace TakahiroNago\AppFoundation\Contracts;

/**
 * Implemented by the application's user model. How the administrator flag is stored (a boolean
 * column, a role column) is the application's own business.
 */
interface Administrator
{
    public function isAdmin(): bool;

    /**
     * Makes this user an administrator and saves it.
     */
    public function grantAdminAccess(): void;
}
