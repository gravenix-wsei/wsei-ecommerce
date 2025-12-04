<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Wsei\Ecommerce\Repository\AddressRepository;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
#[ORM\Table(name: 'address')]
class Address
{
    public const MAX_LENGTH_FIRST_NAME = 100;

    public const MAX_LENGTH_LAST_NAME = 100;

    public const MAX_LENGTH_STREET = 255;

    public const MAX_LENGTH_ZIPCODE = 20;

    public const MAX_LENGTH_CITY = 100;

    public const MAX_LENGTH_COUNTRY = 100;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: self::MAX_LENGTH_FIRST_NAME)]
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_LENGTH_FIRST_NAME)]
    private ?string $firstName = null;

    #[ORM\Column(type: 'string', length: self::MAX_LENGTH_LAST_NAME)]
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_LENGTH_LAST_NAME)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'string', length: self::MAX_LENGTH_STREET)]
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_LENGTH_STREET)]
    private ?string $street = null;

    #[ORM\Column(type: 'string', length: self::MAX_LENGTH_ZIPCODE)]
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_LENGTH_ZIPCODE)]
    private ?string $zipcode = null;

    #[ORM\Column(type: 'string', length: self::MAX_LENGTH_CITY)]
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_LENGTH_CITY)]
    private ?string $city = null;

    #[ORM\Column(type: 'string', length: self::MAX_LENGTH_COUNTRY)]
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_LENGTH_COUNTRY)]
    private ?string $country = null;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'addresses')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Customer $customer = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getZipcode(): ?string
    {
        return $this->zipcode;
    }

    public function setZipcode(string $zipcode): self
    {
        $this->zipcode = $zipcode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

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

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
}
