<?php

namespace App\Entity;

use App\Entity\Enum\BikeType;
use App\Repository\TireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TireRepository::class)]
#[ORM\Table(name: 'tires')]
class Tire
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', enumType: BikeType::class)]
    private BikeType $bikeType;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $scores = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2)]
    private string $priceEur;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $avgKmLifetime = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getBikeType(): BikeType
    {
        return $this->bikeType;
    }

    public function setBikeType(BikeType $bikeType): static
    {
        $this->bikeType = $bikeType;

        return $this;
    }

    public function getScores(): ?array
    {
        return $this->scores;
    }

    public function setScores(?array $scores): static
    {
        $this->scores = $scores;

        return $this;
    }

    public function getPriceEur(): string
    {
        return $this->priceEur;
    }

    public function setPriceEur(string $priceEur): static
    {
        $this->priceEur = $priceEur;

        return $this;
    }

    public function getAvgKmLifetime(): ?int
    {
        return $this->avgKmLifetime;
    }

    public function setAvgKmLifetime(?int $avgKmLifetime): static
    {
        $this->avgKmLifetime = $avgKmLifetime;

        return $this;
    }
}
