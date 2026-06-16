<?php

namespace App\Entity;

use App\Entity\Enum\BikeType;
use App\Entity\Enum\RiderLevel;
use App\Entity\Enum\UsageType;
use App\Repository\CyclistProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CyclistProfileRepository::class)]
#[ORM\Table(name: 'cyclist_profiles')]
class CyclistProfile
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', enumType: BikeType::class)]
    private BikeType $bikeType;

    #[ORM\Column(type: 'string', enumType: RiderLevel::class)]
    private RiderLevel $riderLevel;

    #[ORM\Column(type: 'string', enumType: UsageType::class)]
    private UsageType $usageType;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $preferences = null;

    public function getId(): ?Uuid
    {
        return $this->id;
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

    public function getBikeType(): BikeType
    {
        return $this->bikeType;
    }

    public function setBikeType(BikeType $bikeType): static
    {
        $this->bikeType = $bikeType;

        return $this;
    }

    public function getRiderLevel(): RiderLevel
    {
        return $this->riderLevel;
    }

    public function setRiderLevel(RiderLevel $riderLevel): static
    {
        $this->riderLevel = $riderLevel;

        return $this;
    }

    public function getUsageType(): UsageType
    {
        return $this->usageType;
    }

    public function setUsageType(UsageType $usageType): static
    {
        $this->usageType = $usageType;

        return $this;
    }

    public function getPreferences(): ?array
    {
        return $this->preferences;
    }

    public function setPreferences(?array $preferences): static
    {
        $this->preferences = $preferences;

        return $this;
    }
}
