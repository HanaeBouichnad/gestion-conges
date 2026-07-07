<?php

namespace App\Entity;

use App\Repository\JournalSoldeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JournalSoldeRepository::class)]
class JournalSolde
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $employe;

    #[ORM\ManyToOne(targetEntity: TypeConge::class)]
    #[ORM\JoinColumn(nullable: false)]
    private TypeConge $typeConge;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $auteur; // RH ayant fait la modification

    #[ORM\Column]
    private \DateTimeImmutable $dateModification;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 1)]
    private string $nombreJours; // positif ou négatif

    // RG-32 : note explicative obligatoire
    #[ORM\Column(type: 'text')]
    private string $note;

    public function __construct() { $this->dateModification = new \DateTimeImmutable(); }

    public function getId(): ?int { return $this->id; }
    public function getEmploye(): User { return $this->employe; }
    public function setEmploye(User $e): static { $this->employe = $e; return $this; }
    public function getTypeConge(): TypeConge { return $this->typeConge; }
    public function setTypeConge(TypeConge $t): static { $this->typeConge = $t; return $this; }
    public function getAuteur(): User { return $this->auteur; }
    public function setAuteur(User $a): static { $this->auteur = $a; return $this; }
    public function getDateModification(): \DateTimeImmutable { return $this->dateModification; }
    public function getNombreJours(): float { return (float) $this->nombreJours; }
    public function setNombreJours(float $n): static { $this->nombreJours = (string) $n; return $this; }
    public function getNote(): string { return $this->note; }
    public function setNote(string $n): static { $this->note = $n; return $this; }

    public function setDateModification(\DateTimeImmutable $dateModification): static
    {
        $this->dateModification = $dateModification;

        return $this;
    }
}