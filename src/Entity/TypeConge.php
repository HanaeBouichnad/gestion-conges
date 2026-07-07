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

    // RG-19 / réponse chef de projet point 3 : pièce jointe obligatoire selon le type (ex. congé maladie)
    #[ORM\Column]
    private bool $pieceJointeObligatoire = false;

    #[ORM\Column]
    private bool $actif = true;

    public function getId(): ?int { return $this->id; }
    public function getLibelle(): string { return $this->libelle; }
    public function setLibelle(string $libelle): static { $this->libelle = $libelle; return $this; }
    public function isPieceJointeObligatoire(): bool { return $this->pieceJointeObligatoire; }
    public function setPieceJointeObligatoire(bool $v): static { $this->pieceJointeObligatoire = $v; return $this; }
    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $actif): static { $this->actif = $actif; return $this; }
}