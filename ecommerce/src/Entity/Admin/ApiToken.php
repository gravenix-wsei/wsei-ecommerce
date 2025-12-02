<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Entity\Admin;

use Doctrine\ORM\Mapping as ORM;
use Wsei\Ecommerce\Repository\Admin\ApiTokenRepository;

#[ORM\Entity(repositoryClass: ApiTokenRepository::class)]
#[ORM\Table(name: 'api_token')]
class ApiToken
{
    private const TOKEN_LENGTH = 48;

    private const CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 48, unique: true)]
    private ?string $token = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\OneToOne(targetEntity: Customer::class, inversedBy: 'apiToken')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Customer $customer = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->expiresAt = new \DateTime('+1 hour');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeInterface $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTime();
    }

    public function extendExpiration(): self
    {
        $this->expiresAt = new \DateTime('+1 hour');

        return $this;
    }

    public static function generate(): string
    {
        $token = '';
        $maxIndex = strlen(self::CHARACTERS) - 1;

        for ($i = 0; $i < self::TOKEN_LENGTH; $i++) {
            $token .= self::CHARACTERS[random_int(0, $maxIndex)];
        }

        return $token;
    }
}
