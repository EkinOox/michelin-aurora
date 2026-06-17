<?php

namespace App\Entity;

use App\Repository\FriendshipRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: FriendshipRepository::class)]
#[ORM\Table(name: 'friendships')]
#[ORM\UniqueConstraint(name: 'uniq_friendship', columns: ['user_from_id', 'user_to_id'])]
class Friendship
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_from_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $userFrom;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_to_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $userTo;

    /** pending | accepted */
    #[ORM\Column(length: 20)]
    private string $status = 'pending';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid { return $this->id; }

    public function getUserFrom(): User { return $this->userFrom; }
    public function setUserFrom(User $user): static { $this->userFrom = $user; return $this; }

    public function getUserTo(): User { return $this->userTo; }
    public function setUserTo(User $user): static { $this->userTo = $user; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
