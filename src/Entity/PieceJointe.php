<?php

namespace App\Entity;

use App\Repository\PieceJointeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PieceJointeRepository::class)]
class PieceJointe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DemandeConge::class, inversedBy: 'piecesJointes')]
    #[ORM\JoinColumn(nullable: false)]
    private DemandeConge $demandeConge;

    #[ORM\Column(length: 255)]
    private string $nomFichier;

    #[ORM\Column(length: 255)]
    private string $cheminFichier;

    #[ORM\Column]
    private \DateTimeImmutable $dateAjout;

    public function __construct() { $this->dateAjout = new \DateTimeImmutable(); }

    public function getId(): ?int { return $this->id; }
    public function getDemandeConge(): DemandeConge { return $this->demandeConge; }
    public function setDemandeConge(DemandeConge $d): static { $this->demandeConge = $d; return $this; }
    public function getNomFichier(): string { return $this->nomFichier; }
    public function setNomFichier(string $n): static { $this->nomFichier = $n; return $this; }
    public function getCheminFichier(): string { return $this->cheminFichier; }
    public function setCheminFichier(string $c): static { $this->cheminFichier = $c; return $this; }
    public function getDateAjout(): \DateTimeImmutable { return $this->dateAjout; }

    public function setDateAjout(\DateTimeImmutable $dateAjout): static
    {
        $this->dateAjout = $dateAjout;

        return $this;
    }
}