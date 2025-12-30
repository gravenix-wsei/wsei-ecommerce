<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Framework\Admin\Settings;

final class SettingItem
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $icon,
        public readonly string $url,
        public readonly int $position
    ) {
    }

    public static function fromController(EcommerceSettingsInterface $controller, string $url): self
    {
        return new self(
            name: $controller->getName(),
            description: $controller->getDescription(),
            icon: $controller->getIcon(),
            url: $url,
            position: $controller->getPosition()
        );
    }

    public function compareTo(self $other): int
    {
        if ($this->position === $other->position) {
            return strcasecmp($this->name, $other->name);
        }
        return $this->position <=> $other->position;
    }
}
