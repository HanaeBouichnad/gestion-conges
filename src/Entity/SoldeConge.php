<?php

namespace App\Entity;

use App\Repository\SoldeCongeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SoldeCongeRepository::class)]
#[ORM\UniqueConstraint(columns: ['employe_id', 'type_conge_id'])]
class SoldeConge
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

    #[ORM\Column(type: 'decimal', precision: 5, scale: 1)]
    private string $solde = '0.0';

    public function getId(): ?int { return $this->id; }
    public function getEmploye(): User { return $this->employe; }
    public function setEmploye(User $e): static { $this->employe = $e; return $this; }
    public function getTypeConge(): TypeConge { return $this->typeConge; }
    public function setTypeConge(TypeConge $t): static { $this->typeConge = $t; return $this; }
    public function getSolde(): float { return (float) $this->solde; }
    public function setSolde(float $s): static { $this->solde = (string) $s; return $this; }
}