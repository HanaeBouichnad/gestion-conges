<?php

namespace App\Entity;

use App\Repository\JourFerieRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JourFerieRepository::class)]
class JourFerie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $date;

    #[ORM\Column(length: 150)]
    private string $libelle;

    // RG-46 : récurrent ou ponctuel
    #[ORM\Column]
    private bool $recurrent = true;

    public function getId(): ?int { return $this->id; }
    public function getDate(): \DateTimeImmutable { return $this->date; }
    public function setDate(\DateTimeImmutable $d): static { $this->date = $d; return $this; }
    public function getLibelle(): string { return $this->libelle; }
    public function setLibelle(string $l): static { $this->libelle = $l; return $this; }
    public function isRecurrent(): bool { return $this->recurrent; }
    public function setRecurrent(bool $r): static { $this->recurrent = $r; return $this; }
}