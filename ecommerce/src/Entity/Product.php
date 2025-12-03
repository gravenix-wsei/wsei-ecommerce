<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Wsei\Ecommerce\Repository\ProductRepository;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'product')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    private ?int $stock = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $priceNet = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $priceGross = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): self
    {
        $this->stock = $stock;

        return $this;
    }

    public function getPriceNet(): ?string
    {
        return $this->priceNet;
    }

    public function setPriceNet(string $priceNet): self
    {
        $this->priceNet = $priceNet;

        return $this;
    }

    public function getPriceGross(): ?string
    {
        return $this->priceGross;
    }

    public function setPriceGross(string $priceGross): self
    {
        $this->priceGross = $priceGross;

        return $this;
    }
}
