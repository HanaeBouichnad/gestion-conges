<?php

namespace App\Security\Voter;

use App\Entity\DemandeConge;
use App\Entity\User;
use App\Enum\StatutDemande;
use App\Repository\DemandeAnnulationRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DemandeCongeVoter extends Voter
{
    public const EDIT = 'DEMANDE_EDIT';
    public const DELETE = 'DEMANDE_DELETE';
    public const VIEW = 'DEMANDE_VIEW';
    public const ENVOYER = 'DEMANDE_ENVOYER';
    public const APPROUVER = 'DEMANDE_APPROUVER';
    public const DEMANDER_ANNULATION = 'DEMANDE_ANNULATION_DEMANDER';

    public function __construct(private readonly DemandeAnnulationRepository $demandeAnnulationRepository)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE, self::VIEW, self::ENVOYER, self::APPROUVER, self::DEMANDER_ANNULATION], true)
            && $subject instanceof DemandeConge;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var DemandeConge $demande */
        $demande = $subject;

        return match ($attribute) {
            self::EDIT => $this->estAuteur($demande, $user)
                && $demande->getStatut() === StatutDemande::BROUILLON,
            self::DELETE => $this->estAuteur($demande, $user)
                && in_array($demande->getStatut(), [StatutDemande::BROUILLON, StatutDemande::EN_ATTENTE], true),
            self::ENVOYER => $this->estAuteur($demande, $user)
                && $demande->getStatut() === StatutDemande::BROUILLON,
            self::APPROUVER => $this->estApprobateur($demande, $user)
                && $demande->getStatut() === StatutDemande::EN_ATTENTE,
            self::DEMANDER_ANNULATION => $this->estAuteur($demande, $user)
                && $demande->getStatut() === StatutDemande::APPROUVE
                && $this->demandeAnnulationRepository->findOneEnAttentePour($demande) === null,
            self::VIEW => $this->estAuteur($demande, $user)
                || $this->estResponsableDe($demande, $user)
                || in_array('ROLE_RH', $user->getRoles(), true),
            default => false,
        };
    }

    private function estAuteur(DemandeConge $demande, User $user): bool
    {
        return $demande->getEmploye()->getId() === $user->getId();
    }

    private function estResponsableDe(DemandeConge $demande, User $user): bool
    {
        return $demande->getEmploye()->getResponsable()?->getId() === $user->getId();
    }

    private function estApprobateur(DemandeConge $demande, User $user): bool
    {
        return $demande->getApprobateur()->getId() === $user->getId();
    }
}