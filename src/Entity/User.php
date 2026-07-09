<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private string $password;

    #[ORM\Column(length: 100)]
    private string $nom;

    #[ORM\Column(length: 100)]
    private string $prenom;

    // RG-07 / RG-14 : responsable hiérarchique direct, sert d'approbateur par défaut (figé, cf RG-14 validé)
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'subordonnes')]
    private ?self $responsable = null;

    #[ORM\OneToMany(mappedBy: 'responsable', targetEntity: self::class)]
    private Collection $subordonnes;
  

    public function __construct()
    {
        $this->subordonnes = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getUserIdentifier(): string { return $this->email; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function eraseCredentials(): void {}

    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getPrenom(): string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }

    public function getNomComplet(): string { return $this->prenom . ' ' . $this->nom; }

    public function getResponsable(): ?self { return $this->responsable; }
    public function setResponsable(?self $responsable): static { $this->responsable = $responsable; return $this; }

    /**
     * @return Collection<int, User>
     */
    public function getSubordonnes(): Collection
    {
        return $this->subordonnes;
    }

    public function addSubordonne(User $subordonne): static
    {
        if (!$this->subordonnes->contains($subordonne)) {
            $this->subordonnes->add($subordonne);
            $subordonne->setResponsable($this);
        }

        return $this;
    }

    public function removeSubordonne(User $subordonne): static
    {
        if ($this->subordonnes->removeElement($subordonne)) {
            if ($subordonne->getResponsable() === $this) {
                $subordonne->setResponsable(null);
            }
        }

        return $this;
    }
}