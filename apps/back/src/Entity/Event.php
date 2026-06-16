<?php

namespace App\Entity;

use App\Entity\Enum\EventType;
use App\Repository\EventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'events')]
class Event
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 32)]
    private string $date;

    #[ORM\Column(length: 255)]
    private string $place;

    #[ORM\Column(length: 32)]
    private string $distanceLabel;

    #[ORM\Column(type: 'string', enumType: EventType::class)]
    private EventType $type;

    #[ORM\Column(type: Types::FLOAT)]
    private float $kmAway = 0.0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $riders = 0;

    #[ORM\Column(length: 64)]
    private string $imageKey;

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

    public function getDate(): string
    {
        return $this->date;
    }

    public function setDate(string $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getPlace(): string
    {
        return $this->place;
    }

    public function setPlace(string $place): static
    {
        $this->place = $place;

        return $this;
    }

    public function getDistanceLabel(): string
    {
        return $this->distanceLabel;
    }

    public function setDistanceLabel(string $distanceLabel): static
    {
        $this->distanceLabel = $distanceLabel;

        return $this;
    }

    public function getType(): EventType
    {
        return $this->type;
    }

    public function setType(EventType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getKmAway(): float
    {
        return $this->kmAway;
    }

    public function setKmAway(float $kmAway): static
    {
        $this->kmAway = $kmAway;

        return $this;
    }

    public function getRiders(): int
    {
        return $this->riders;
    }

    public function setRiders(int $riders): static
    {
        $this->riders = $riders;

        return $this;
    }

    public function getImageKey(): string
    {
        return $this->imageKey;
    }

    public function setImageKey(string $imageKey): static
    {
        $this->imageKey = $imageKey;

        return $this;
    }
}
