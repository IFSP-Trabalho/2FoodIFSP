<?php

namespace App\Services;

class UserCreationLimitService
{
    /**
     * Prepared for future license policy.
     * TODO: enforce admin-released user quota when business rule is enabled.
     */
    public function canCreateUserByPlan(): bool
    {
        return true;
    }

    public function getLimitErrorMessage(): string
    {
        return 'Limite de usuarios liberados pelo admin atingido.';
    }
}
