<?php

namespace App\Entity;

use App\Entity\Enum\RewardsLevel;
use App\Repository\CommunityRiderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CommunityRiderRepository::class)]
#[ORM\Table(name: 'community_riders')]
class CommunityRider
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 4)]
    private string $initials;

    #[ORM\Column(type: 'string', enumType: RewardsLevel::class)]
    private RewardsLevel $rank;

    #[ORM\Column(type: Types::FLOAT)]
    private float $kmThisMonth = 0.0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $matchPercent = 0;

    #[ORM\Column(length: 16)]
    private string $colorHex;

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

    public function getInitials(): string
    {
        return $this->initials;
    }

    public function setInitials(string $initials): static
    {
        $this->initials = $initials;

        return $this;
    }

    public function getRank(): RewardsLevel
    {
        return $this->rank;
    }

    public function setRank(RewardsLevel $rank): static
    {
        $this->rank = $rank;

        return $this;
    }

    public function getKmThisMonth(): float
    {
        return $this->kmThisMonth;
    }

    public function setKmThisMonth(float $kmThisMonth): static
    {
        $this->kmThisMonth = $kmThisMonth;

        return $this;
    }

    public function getMatchPercent(): int
    {
        return $this->matchPercent;
    }

    public function setMatchPercent(int $matchPercent): static
    {
        $this->matchPercent = $matchPercent;

        return $this;
    }

    public function getColorHex(): string
    {
        return $this->colorHex;
    }

    public function setColorHex(string $colorHex): static
    {
        $this->colorHex = $colorHex;

        return $this;
    }
}
