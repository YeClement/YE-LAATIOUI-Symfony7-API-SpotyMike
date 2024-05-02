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

    #[ORM\Column(type: "boolean")]
    private bool $featuring = false; // Field to mark if this is a featuring song

    // Optional: Reference to the featured artist
    #[ORM\ManyToOne(targetEntity: Artist::class)]
    #[ORM\JoinColumn(name: "featured_artist_id", referencedColumnName: "id", nullable: true)]
    private ?Artist $featuredArtist = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable(); // Ensure the creation date is automatically set
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
        return $this;
    }

    public function isFeaturing(): bool
    {
        return $this->featuring;
    }

    public function setFeaturing(bool $featuring): self
    {
        $this->featuring = $featuring;
        return $this;
    }

    public function getFeaturedArtist(): ?Artist
    {
        return $this->featuredArtist;
    }

    public function setFeaturedArtist(?Artist $artist): self
    {
        $this->featuredArtist = $artist;
        return $this;
    }

    public function serializer(bool $includeFeaturing = false): array
    {
        $data = [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'cover' => $this->getCover(),
            'createdAt' => $this->getCreatedAt()->format('c')
        ];

        if ($includeFeaturing && $this->isFeaturing() && $this->getFeaturedArtist()) {
          
            $data['featuredArtist'] = $this->getFeaturedArtist()->getFullname();
        }

        return $data;
    }
}
