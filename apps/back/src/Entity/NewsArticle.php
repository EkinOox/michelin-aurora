<?php

namespace App\Entity;

use App\Repository\NewsArticleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: NewsArticleRepository::class)]
#[ORM\Table(name: 'news_articles')]
class NewsArticle
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 64)]
    private string $tag;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 32)]
    private string $date;

    #[ORM\Column(length: 16)]
    private string $readTime;

    #[ORM\Column(length: 64)]
    private string $imageKey;

    #[ORM\Column(type: Types::TEXT)]
    private string $body;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setTag(string $tag): static
    {
        $this->tag = $tag;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

    public function getReadTime(): string
    {
        return $this->readTime;
    }

    public function setReadTime(string $readTime): static
    {
        $this->readTime = $readTime;

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

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;

        return $this;
    }
}
