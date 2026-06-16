<?php

namespace App\Entity;

use App\Entity\Enum\BikeType;
use App\Entity\Enum\Difficulty;
use App\Repository\RouteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: RouteRepository::class)]
#[ORM\Table(name: 'routes')]
class Route
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

    #[ORM\Column(type: 'string', enumType: Difficulty::class)]
    private Difficulty $difficulty;

    #[ORM\Column(type: Types::FLOAT)]
    private float $michelinScore = 0.0;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $geojson = null;

    #[ORM\ManyToOne(targetEntity: Tire::class)]
    #[ORM\JoinColumn(name: 'tire_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Tire $tire = null;

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

    public function getDifficulty(): Difficulty
    {
        return $this->difficulty;
    }

    public function setDifficulty(Difficulty $difficulty): static
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function getMichelinScore(): float
    {
        return $this->michelinScore;
    }

    public function setMichelinScore(float $michelinScore): static
    {
        $this->michelinScore = $michelinScore;

        return $this;
    }

    public function getGeojson(): ?array
    {
        return $this->geojson;
    }

    public function setGeojson(?array $geojson): static
    {
        $this->geojson = $geojson;

        return $this;
    }

    public function getTire(): ?Tire
    {
        return $this->tire;
    }

    public function setTire(?Tire $tire): static
    {
        $this->tire = $tire;

        return $this;
    }
}
