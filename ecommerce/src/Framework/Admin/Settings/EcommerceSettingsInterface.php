<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Framework\Admin\Settings;

interface EcommerceSettingsInterface
{
    /**
     * Get the display name of the setting
     */
    public function getName(): string;

    /**
     * Get the icon filename (e.g., 'settings.svg')
     */
    public function getIcon(): string;

    /**
     * Get the description of the setting (optional)
     */
    public function getDescription(): ?string;

    /**
     * Get the position for sorting (lower numbers appear first)
     * When positions are equal, settings are sorted alphabetically by name
     */
    public function getPosition(): int;

    /**
     * Get the route name entrypoint for this setting
     * Must follow the pattern: admin.settings.{name}
     */
    public function getPathEntrypointName(): string;
}


