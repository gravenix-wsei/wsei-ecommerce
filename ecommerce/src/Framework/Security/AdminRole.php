<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Framework\Security;

enum AdminRole: string
{
    case ROLE_ADMIN = 'ROLE_ADMIN';
    case ROLE_ADMIN_PRODUCT = 'ROLE_ADMIN.PRODUCT';
    case ROLE_ADMIN_CATEGORY = 'ROLE_ADMIN.CATEGORY';
    case ROLE_ADMIN_CUSTOMER = 'ROLE_ADMIN.CUSTOMER';
    case ROLE_ADMIN_ORDER = 'ROLE_ADMIN.ORDER';
    case ROLE_ADMIN_CONFIG = 'ROLE_ADMIN.CONFIG';
    case ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public function getLabel(): string
    {
        return match ($this) {
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_ADMIN_PRODUCT => 'Product Manager',
            self::ROLE_ADMIN_CATEGORY => 'Category Manager',
            self::ROLE_ADMIN_CUSTOMER => 'Customer Manager',
            self::ROLE_ADMIN_ORDER => 'Order Manager',
            self::ROLE_ADMIN_CONFIG => 'Configuration Manager',
            self::ROLE_SUPER_ADMIN => 'Super Administrator',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ROLE_ADMIN => 'Base administrator role with dashboard access',
            self::ROLE_ADMIN_PRODUCT => 'Manage products and inventory',
            self::ROLE_ADMIN_CATEGORY => 'Manage product categories',
            self::ROLE_ADMIN_CUSTOMER => 'Manage customer accounts and addresses',
            self::ROLE_ADMIN_ORDER => 'View and manage customer orders',
            self::ROLE_ADMIN_CONFIG => 'Access system configuration and settings',
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
