<?php

namespace App\Entity;

use App\Repository\AlbumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlbumRepository::class)]
class Album
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 90)]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 20)]
    private ?string $categ = null;

    #[ORM\Column(type: 'string', length: 20)]
    private ?string $label = null;

    #[ORM\Column(type: 'string', length: 125)]
    private ?string $cover = null;

    #[ORM\Column(type: 'string')]
    private ?int $year = null;

    #[ORM\ManyToOne(targetEntity: Artist::class, inversedBy: 'albums')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Artist $artist;


    #[ORM\OneToMany(mappedBy: 'album', targetEntity: Song::class)]
    private Collection $songs;

    


    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->songs = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable(); 
       
            
    
        
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getCateg(): ?string
    {
        return $this->categ;
    }

    public function setCateg(string $categ): self
    {
        $this->categ = $categ;
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
    public function getCover(): ?string
    {
        return $this->cover;
    }

    public function setCover(string $cover): self
    {
        $this->cover = $cover;
        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(string $year): self
    {
        $this->year = $year;
        return $this;
    }

    public function getArtist(): ?Artist
    {
        return $this->artist;
    }

    public function setArtist(?Artist $artist): self
    {
        $this->artist = $artist;
        return $this;
    }

    /**
     * @return Collection<int, Song>
     */
    public function getSongs(): Collection
    {
        return $this->songs;
    }

    public function addSong(Song $song): self
    {
        if (!$this->songs->contains($song)) {
            $this->songs->add($song);
            $song->setAlbum($this);
        }

        return $this;
    }

    public function removeSong(Song $song): self
    {
        if ($this->songs->removeElement($song)) {

            if ($song->getAlbum() === $this) {
                $song->setAlbum(null);
            }
        }

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

    public function serializer(): array
        {

    return [
        'id' => $this->getId(),
        'nom' => $this->getNom(),
        'categ' => $this->getCateg(),
        'label' => $this->getLabel(),
        'cover' => $this->getCover(),
        'year' => $this->getYear(),
        'createdAt' => $this->getCreatedAt()->format('Y-m-d H:i:s'),
        "songs" => $this->getSong() ? $this->getSong()->serializer() : [],
        ];
        }

        
}
