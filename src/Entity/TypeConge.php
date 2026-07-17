<?php

namespace App\Entity;

use App\Repository\TypeCongeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeCongeRepository::class)]
class TypeConge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $libelle;

    #[ORM\Column(type: 'text')]
    private string $description = '';

    // RG-19 / réponse chef de projet point 3 : pièce jointe obligatoire selon le type (ex. congé maladie)
    #[ORM\Column]
    private bool $pieceJointeObligatoire = false;

    #[ORM\Column]
    private bool $actif = true;

    // solde maximum de référence en jours pour ce type de congé ; null = pas de limite (ex. congé sans solde)
    #[ORM\Column(type: 'decimal', precision: 5, scale: 1, nullable: true)]
    private ?string $soldeMax = null;

    public function getId(): ?int { return $this->id; }
    public function getLibelle(): string { return $this->libelle; }
    public function setLibelle(string $libelle): static { $this->libelle = $libelle; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }
    public function isPieceJointeObligatoire(): bool { return $this->pieceJointeObligatoire; }
    public function setPieceJointeObligatoire(bool $v): static { $this->pieceJointeObligatoire = $v; return $this; }
    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $actif): static { $this->actif = $actif; return $this; }
    public function getSoldeMax(): ?float { return $this->soldeMax !== null ? (float) $this->soldeMax : null; }
    public function setSoldeMax(?float $soldeMax): static { $this->soldeMax = $soldeMax !== null ? (string) $soldeMax : null; return $this; }
}