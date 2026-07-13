<?php

namespace App\Entity;

use App\Enum\StatutDemandeAnnulation;
use App\Repository\DemandeAnnulationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DemandeAnnulationRepository::class)]
class DemandeAnnulation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DemandeConge::class)]
    #[ORM\JoinColumn(nullable: false)]
    private DemandeConge $demandeConge;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $demandeur;

    #[ORM\Column(type: 'text')]
    private string $motif = '';

    #[ORM\Column(enumType: StatutDemandeAnnulation::class)]
    private StatutDemandeAnnulation $statut = StatutDemandeAnnulation::EN_ATTENTE;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $traitePar = null;

    #[ORM\Column]
    private \DateTimeImmutable $dateCreation;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateTraitement = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getDemandeConge(): DemandeConge { return $this->demandeConge; }
    public function setDemandeConge(DemandeConge $d): static { $this->demandeConge = $d; return $this; }

    public function getDemandeur(): User { return $this->demandeur; }
    public function setDemandeur(User $u): static { $this->demandeur = $u; return $this; }

    public function getMotif(): string { return $this->motif; }
    public function setMotif(string $m): static { $this->motif = $m; return $this; }

    public function getStatut(): StatutDemandeAnnulation { return $this->statut; }
    public function setStatut(StatutDemandeAnnulation $s): static { $this->statut = $s; return $this; }

    public function getTraitePar(): ?User { return $this->traitePar; }
    public function setTraitePar(?User $u): static { $this->traitePar = $u; return $this; }

    public function getDateCreation(): \DateTimeImmutable { return $this->dateCreation; }

    public function getDateTraitement(): ?\DateTimeImmutable { return $this->dateTraitement; }
    public function setDateTraitement(?\DateTimeImmutable $d): static { $this->dateTraitement = $d; return $this; }
}
