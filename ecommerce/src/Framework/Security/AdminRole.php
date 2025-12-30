<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Framework\Security;

enum AdminRole: string
{
    case ROLE_ADMIN = 'ROLE_ADMIN';
    case ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public function getLabel(): string
    {
        return match ($this) {
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_SUPER_ADMIN => 'Super Administrator',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ROLE_ADMIN => 'Standard administrator with access to all management features',
            self::ROLE_SUPER_ADMIN => 'Super administrator with full system control including user management',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function getChoices(): array
    {
        $choices = [];
        foreach (self::cases() as $role) {
            $choices[$role->getLabel()] = $role->value;
        }
        return $choices;
    }

    /**
     * Get all available roles as array of values
     *
     * @return array<int, string>
     */
    public static function getAllValues(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }
}
