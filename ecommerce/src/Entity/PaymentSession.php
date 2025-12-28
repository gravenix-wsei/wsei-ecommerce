<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Entity;

use Doctrine\ORM\Mapping as ORM;
use Wsei\Ecommerce\Framework\Payment\Stripe\PaymentSessionStatus;
use Wsei\Ecommerce\Repository\PaymentSessionRepository;

#[ORM\Entity(repositoryClass: PaymentSessionRepository::class)]
#[ORM\Table(name: 'payment_session')]
class PaymentSession
{
    public const TOKEN_LENGTH = 128;

    private const CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 128, unique: true)]
    private ?string $token = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripeSessionId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $paymentIntentId = null;

    #[ORM\Column(type: 'text')]
    private ?string $returnUrl = null;

    #[ORM\Column(type: 'string', length: 20, enumType: PaymentSessionStatus::class)]
    private PaymentSessionStatus $status = PaymentSessionStatus::ACTIVE;

    #[ORM\ManyToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Order $order = null;

    public function __construct()
    {
        $this->token = self::generate();
        $this->createdAt = new \DateTime();
        $this->expiresAt = new \DateTime('+30 minutes');
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

    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(?string $stripeSessionId): self
    {
        $this->stripeSessionId = $stripeSessionId;

        return $this;
    }

    public function getPaymentIntentId(): ?string
    {
        return $this->paymentIntentId;
    }

    public function setPaymentIntentId(?string $paymentIntentId): self
    {
        $this->paymentIntentId = $paymentIntentId;

        return $this;
    }

    public function getReturnUrl(): ?string
    {
        return $this->returnUrl;
    }

    public function setReturnUrl(string $returnUrl): self
    {
        $this->returnUrl = $returnUrl;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getStatus(): PaymentSessionStatus
    {
        return $this->status;
    }

    public function setStatus(PaymentSessionStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === PaymentSessionStatus::ACTIVE;
    }

    public function isCancelled(): bool
    {
        return $this->status === PaymentSessionStatus::CANCELLED;
    }

    public function isCompleted(): bool
    {
        return $this->status === PaymentSessionStatus::COMPLETED;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTime() || $this->status === PaymentSessionStatus::EXPIRED;
    }

    public function cancel(): void
    {
        $this->status = PaymentSessionStatus::CANCELLED;
    }

    public function complete(): void
    {
        $this->status = PaymentSessionStatus::COMPLETED;
    }

    public function expire(): void
    {
        $this->status = PaymentSessionStatus::EXPIRED;
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
