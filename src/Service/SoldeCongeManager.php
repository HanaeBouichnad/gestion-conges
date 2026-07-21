<?php

namespace App\Service;

use App\Entity\DemandeConge;
use App\Entity\JournalSolde;
use App\Entity\SoldeConge;
use App\Entity\TypeConge;
use App\Entity\User;
use App\Repository\SoldeCongeRepository;
use Doctrine\ORM\EntityManagerInterface;

class SoldeCongeManager
{
    public function __construct(
        private readonly SoldeCongeRepository $soldeCongeRepository,
        private readonly JoursOuvresCalculator $joursOuvresCalculator,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Retourne le SoldeConge de l'employé pour ce type, ou en crée un initialisé
     * au plafond de référence du type (TypeConge.soldeMax) si l'employé n'en a pas encore.
     */
    public function trouverOuCreerSolde(User $employe, TypeConge $type): SoldeConge
    {
        $solde = $this->soldeCongeRepository->findOneParEmployeEtType($employe, $type);
        if ($solde) {
            return $solde;
        }

        $solde = new SoldeConge();
        $solde->setEmploye($employe);
        $solde->setTypeConge($type);
        $solde->setSolde($type->getSoldeMax() ?? 0.0);
        $this->em->persist($solde);

        return $solde;
    }

    /**
     * Vérifie que la demande ne dépasse pas le solde disponible.
     * Retourne un message d'erreur si le solde est dépassé, null sinon (y compris pour un type illimité).
     */
    public function verifierDisponibilite(DemandeConge $demande): ?string
    {
        $type = $demande->getTypeConge();
        if ($type->getSoldeMax() === null) {
            return null;
        }

        $jours = $this->joursOuvresCalculator->calculer($demande);
        $solde = $this->trouverOuCreerSolde($demande->getEmploye(), $type);

        if ($jours > $solde->getSolde()) {
            return sprintf(
                'Solde insuffisant pour "%s" : %s j. ouvré(s) demandé(s), %s j. disponible(s).',
                $type->getLibelle(),
                $jours,
                $solde->getSolde()
            );
        }

        return null;
    }

    /** Déduit le nombre de jours ouvrés de la demande du solde de l'employé, avec traçabilité. */
    public function deduire(DemandeConge $demande, User $auteur): void
    {
        $jours = $this->joursOuvresCalculator->calculer($demande);

        $this->ajuster($demande, $auteur, -$jours, sprintf(
            "Déduction automatique suite à l'approbation de la demande de congé #%d (%s j. ouvré(s))",
            $demande->getId(),
            $jours
        ));
    }

    /** Recrédite le nombre de jours ouvrés de la demande au solde de l'employé, avec traçabilité. */
    public function crediter(DemandeConge $demande, User $auteur, string $note): void
    {
        $jours = $this->joursOuvresCalculator->calculer($demande);

        $this->ajuster($demande, $auteur, $jours, $note);
    }

    private function ajuster(DemandeConge $demande, User $auteur, float $jours, string $note): void
    {
        $type = $demande->getTypeConge();
        if ($type->getSoldeMax() === null) {
            return;
        }

        $solde = $this->trouverOuCreerSolde($demande->getEmploye(), $type);
        $solde->setSolde($solde->getSolde() + $jours);

        $journal = new JournalSolde();
        $journal->setEmploye($demande->getEmploye());
        $journal->setTypeConge($type);
        $journal->setAuteur($auteur);
        $journal->setNombreJours($jours);
        $journal->setNote($note);
        $this->em->persist($journal);
    }
}
