<?php

namespace App\Entity;

use App\Enum\OrigineAnnulation;
use App\Enum\PeriodeJournee;
use App\Enum\StatutDemande;
use App\Repository\DemandeCongeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DemandeCongeRepository::class)]
class DemandeConge
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

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $dateDebut;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $dateFin;

    // RG-12
    #[ORM\Column(enumType: PeriodeJournee::class)]
    private PeriodeJournee $periode = PeriodeJournee::JOURNEE_COMPLETE;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // RG-13/RG-14 : approbateur = responsable hiérarchique déclaré (figé, cf réponse #6)
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $approbateur;

    #[ORM\Column(enumType: StatutDemande::class)]
    private StatutDemande $statut = StatutDemande::BROUILLON;

    // RG-25 + réponse #4 : motif de refus obligatoire
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $motifRefus = null;

    // renseigné uniquement quand statut passe à ANNULE, distingue "Annulée" (employé) de "Annulée par RH"
    #[ORM\Column(enumType: OrigineAnnulation::class, nullable: true)]
    private ?OrigineAnnulation $origineAnnulation = null;

    #[ORM\Column]
    private \DateTimeImmutable $dateCreation;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateModification = null;

    #[ORM\OneToMany(mappedBy: 'demandeConge', targetEntity: PieceJointe::class, cascade: ['persist', 'remove'])]
    private Collection $piecesJointes;

    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
        $this->piecesJointes = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getEmploye(): User { return $this->employe; }
    public function setEmploye(User $employe): static { $this->employe = $employe; return $this; }

    public function getTypeConge(): TypeConge { return $this->typeConge; }
    public function setTypeConge(TypeConge $typeConge): static { $this->typeConge = $typeConge; return $this; }

    public function getDateDebut(): \DateTimeImmutable { return $this->dateDebut; }
    public function setDateDebut(\DateTimeImmutable $d): static { $this->dateDebut = $d; return $this; }

    public function getDateFin(): \DateTimeImmutable { return $this->dateFin; }
    public function setDateFin(\DateTimeImmutable $d): static { $this->dateFin = $d; return $this; }

    public function getPeriode(): PeriodeJournee { return $this->periode; }
    public function setPeriode(PeriodeJournee $p): static { $this->periode = $p; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): static { $this->description = $d; return $this; }

    public function getApprobateur(): User { return $this->approbateur; }
    public function setApprobateur(User $a): static { $this->approbateur = $a; return $this; }

    public function getStatut(): StatutDemande { return $this->statut; }
    public function setStatut(StatutDemande $s): static { $this->statut = $s; return $this; }

    public function getMotifRefus(): ?string { return $this->motifRefus; }
    public function setMotifRefus(?string $m): static { $this->motifRefus = $m; return $this; }

    public function getOrigineAnnulation(): ?OrigineAnnulation { return $this->origineAnnulation; }
    public function setOrigineAnnulation(?OrigineAnnulation $o): static { $this->origineAnnulation = $o; return $this; }

    public function getStatutLabel(): string
    {
        if ($this->statut === StatutDemande::ANNULE) {
            return $this->origineAnnulation === OrigineAnnulation::RH
                ? OrigineAnnulation::RH->label()
                : OrigineAnnulation::EMPLOYE->label();
        }

        return $this->statut->label();
    }

    public function getNombreJours(): float
    {
        $jours = $this->dateDebut->diff($this->dateFin)->days + 1;

        if ($this->periode !== PeriodeJournee::JOURNEE_COMPLETE && $this->dateDebut == $this->dateFin) {
            return 0.5;
        }

        return (float) $jours;
    }

    public function getDateCreation(): \DateTimeImmutable { return $this->dateCreation; }

    public function getDateModification(): ?\DateTimeImmutable { return $this->dateModification; }
    public function setDateModification(?\DateTimeImmutable $d): static { $this->dateModification = $d; return $this; }

    /** @return Collection<int, PieceJointe> */
    public function getPiecesJointes(): Collection { return $this->piecesJointes; }

    public function addPieceJointe(PieceJointe $p): static
    {
        if (!$this->piecesJointes->contains($p)) {
            $this->piecesJointes->add($p);
            $p->setDemandeConge($this);
        }
        return $this;
    }

    public function setDateCreation(\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function addPiecesJointe(PieceJointe $piecesJointe): static
    {
        if (!$this->piecesJointes->contains($piecesJointe)) {
            $this->piecesJointes->add($piecesJointe);
            $piecesJointe->setDemandeConge($this);
        }

        return $this;
    }

    public function removePiecesJointe(PieceJointe $piecesJointe): static
    {
        if ($this->piecesJointes->removeElement($piecesJointe)) {
            // set the owning side to null (unless already changed)
            if ($piecesJointe->getDemandeConge() === $this) {
                $piecesJointe->setDemandeConge(null);
            }
        }

        return $this;
    }
}