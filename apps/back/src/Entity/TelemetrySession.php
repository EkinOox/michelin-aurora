<?php

namespace App\Entity;

use App\Repository\TelemetrySessionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TelemetrySessionRepository::class)]
#[ORM\Table(name: 'telemetry_sessions')]
class TelemetrySession
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Ride::class)]
    #[ORM\JoinColumn(name: 'ride_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Ride $ride;

    #[ORM\Column(type: Types::FLOAT)]
    private float $pressureFrontBar;

    #[ORM\Column(type: Types::FLOAT)]
    private float $pressureRearBar;

    #[ORM\Column(type: Types::FLOAT)]
    private float $speedKmh;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $alertTriggered = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $recordedAt;

    public function __construct()
    {
        $this->recordedAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getRide(): Ride
    {
        return $this->ride;
    }

    public function setRide(Ride $ride): static
    {
        $this->ride = $ride;

        return $this;
    }

    public function getPressureFrontBar(): float
    {
        return $this->pressureFrontBar;
    }

    public function setPressureFrontBar(float $pressureFrontBar): static
    {
        $this->pressureFrontBar = $pressureFrontBar;

        return $this;
    }

    public function getPressureRearBar(): float
    {
        return $this->pressureRearBar;
    }

    public function setPressureRearBar(float $pressureRearBar): static
    {
        $this->pressureRearBar = $pressureRearBar;

        return $this;
    }

    public function getSpeedKmh(): float
    {
        return $this->speedKmh;
    }

    public function setSpeedKmh(float $speedKmh): static
    {
        $this->speedKmh = $speedKmh;

        return $this;
    }

    public function isAlertTriggered(): bool
    {
        return $this->alertTriggered;
    }

    public function setAlertTriggered(bool $alertTriggered): static
    {
        $this->alertTriggered = $alertTriggered;

        return $this;
    }

    public function getRecordedAt(): \DateTimeImmutable
    {
        return $this->recordedAt;
    }
}
