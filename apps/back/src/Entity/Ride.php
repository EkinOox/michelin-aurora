<?php

namespace App\Entity;

use App\Entity\Enum\TerrainType;
use App\Repository\RideRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: RideRepository::class)]
#[ORM\Table(name: 'rides')]
class Ride
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::FLOAT)]
    private float $distanceKm;

    #[ORM\Column(type: Types::INTEGER)]
    private int $elevationM = 0;

    #[ORM\Column(type: 'string', enumType: TerrainType::class)]
    private TerrainType $terrainType;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $weather = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $pointsEarned = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $startedAt;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function hasUser(): bool
    {
        return isset($this->user);
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getDistanceKm(): float
    {
        return $this->distanceKm;
    }

    public function setDistanceKm(float $distanceKm): static
    {
        $this->distanceKm = $distanceKm;

        return $this;
    }

    public function getElevationM(): int
    {
        return $this->elevationM;
    }

    public function setElevationM(int $elevationM): static
    {
        $this->elevationM = $elevationM;

        return $this;
    }

    public function getTerrainType(): TerrainType
    {
        return $this->terrainType;
    }

    public function setTerrainType(TerrainType $terrainType): static
    {
        $this->terrainType = $terrainType;

        return $this;
    }

    public function getWeather(): ?array
    {
        return $this->weather;
    }

    public function setWeather(?array $weather): static
    {
        $this->weather = $weather;

        return $this;
    }

    public function getPointsEarned(): int
    {
        return $this->pointsEarned;
    }

    public function setPointsEarned(int $pointsEarned): static
    {
        $this->pointsEarned = $pointsEarned;

        return $this;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }
}
