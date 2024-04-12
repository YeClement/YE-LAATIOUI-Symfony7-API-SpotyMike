<?php

namespace App\Entity;

use App\Repository\SongRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SongRepository::class)]
class Song
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: "string", length: 125)]
    private ?string $cover = null;

    #[ORM\Column(type: "datetime_immutable")]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: Album::class, inversedBy: 'songs')]
    private ?Album $album = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable(); 
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getCover(): ?string
    {
        return $this->cover;
    }

    public function setCover(string $cover): self
    {
        $this->cover = $cover;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getAlbum(): ?Album
    {
        return $this->album;
    }

    public function setAlbum(?Album $album): self
    {
        $this->album = $album;
        if ($album !== null && !$album->getSongs()->contains($this)) {
            $album->addSong($this);
        }
        return $this;
    }

    public function serializer(): array
    {
        return [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'cover' => $this->getCover(),
            'createdAt' => $this->getCreatedAt()->format('c'), 
            'album' => $this->getAlbum() ? $this->getAlbum()->getId() : null 
        ];
    }
}
