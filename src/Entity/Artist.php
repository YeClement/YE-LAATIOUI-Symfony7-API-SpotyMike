<?php

namespace App\Entity;

use App\Repository\ArtistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArtistRepository::class)]
class Artist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'artist', targetEntity: User::class, cascade: ['persist', 'remove'])]
    private ?User $user = null;

    #[ORM\Column(type: "string", length: 90)]
    private ?string $fullname = null;

    #[ORM\Column(type: "string", length: 90)]
    private ?string $label = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 10)]
    private $active = true;

    
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "follower_id", referencedColumnName: "id", nullable: true)]
    private ?User $follower = null;

    

    #[ORM\OneToMany(mappedBy: 'artist', targetEntity: Album::class, cascade: ['persist', 'remove'])]
    private Collection $albums;

    public function __construct()
    {
        $this->albums = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function setFullname(string $fullname): self
    {
        $this->fullname = $fullname;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;
        return $this;
    }


    public function getActive(): ?bool
    {
        return $this->active ;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
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

    public function getFollower(): ?User
    {
        return $this->follower;
    }

    public function setFollower(?User $follower): self
    {
        $this->follower = $follower;
        return $this;
    }

    public function getAlbums(): Collection
    {
        return $this->albums;
    }

    public function addAlbum(Album $album): self
    {
        if (!$this->albums->contains($album)) {
            $this->albums->add($album);
            $album->setArtist($this);
        }
        return $this;
    }

    public function removeAlbum(Album $album): self
    {
        if ($this->albums->removeElement($album)) {
            if ($album->getArtist() === $this) {
                $album->setArtist(null);
            }
        }
        return $this;
    }




    public function serializer(): array
    {
        $user = $this->getUser();
        $userData = $user ? [
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'sexe' => $user->getSexe() ? "Homme" : "Femme",
            'dateBirth' => $user->getDateBirth()->format('Y-m-d'), 
        ] : null;
    
        return [
            'id' => $this->getId(),
            'fullname' => $this->getFullname(),
            'label' => $this->getLabel(),
            'description' => $this->getDescription(),
            'avatar' => $this->getAvatar(),
            'user' => $userData,
            'albums' => $this->getAlbums()->map(function ($album) {
                return $album->serializer();
            })->toArray(),
        ];
    }
    

}
