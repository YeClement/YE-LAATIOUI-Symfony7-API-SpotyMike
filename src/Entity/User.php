<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 55)]
    private ?string $firstname = null;

    #[ORM\Column(length: 55)]
    private ?string $lastname = null;

    #[ORM\Column(length: 80, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $tel = null;

    #[ORM\Column]
    private ?bool $sexe = null;


    #[ORM\Column(length: 255)]
    private ?string $password = null;


    #[ORM\Column(type: "datetime_immutable")]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: "datetime_immutable")]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateBirth;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Artist::class, cascade: ['persist', 'remove'])]
    private ?Artist $artist = null;
   
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getTel(): ?string
    {
        return $this->tel;
    }

    public function setTel(?string $tel): self
    {
        $this->tel = $tel;
        return $this;
    }

    public function getSexe(): string
    {
        return $this->sexe ? 'homme' : 'femme';
    }

    public function setSexeHommeFemme(string $sexe): self
    {
        $this->sexe = $sexe === 'homme';
        return $this;
    }

    public function getDateBirth(): ?\DateTimeInterface
    {
        return $this->dateBirth;
    }

    public function setDateBirth(\DateTimeInterface $dateBirth): self
    {
        $this->dateBirth = $dateBirth;
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
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
    return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
    $this->updatedAt = $updatedAt;
    return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }
    
    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }
    
    public function getArtist(): ?Artist
    {
        return $this->artist;
    }

    public function setArtist(?Artist $artist): self
    {
        
        if ($artist !== null && $artist->getUser() !== $this) {
            $artist->setUser($this);
        }

        $this->artist = $artist;

        return $this;
    }

    public function getRoles(): array{

        return [];
    }

    public function eraseCredentials(): void{

    }


    public function getUserIdentifier(): string
    {
        return $this->email; 
    }

    public function serializer(): array
    {
        return [
            "id" => $this->getId(),
            "firstname" => $this->getFirstname(),
            "lastname" => $this->getLastname(),
            "email" => $this->getEmail(),
            "tel" => $this->getTel(),
            "sexe" => $this->getSexe(),
            "dateBirth" => $this->getDateBirth() ? $this->getDateBirth()->format('Y-m-d') : null,
            "createdAt" => $this->getCreatedAt()->format('c'),
            "updatedAt" => $this->getUpdatedAt()->format('c'),
            "artist" => $this->getArtist() ? $this->getArtist()->serializer() : null,
        ];
    }
    

public function __construct() {
    $this->createdAt = new \DateTimeImmutable();
    $this->updatedAt = new \DateTimeImmutable();
    $this->dateBirth = new \DateTimeImmutable(); 
}

    
    
    

}