<?php

namespace App\Controller;

use App\Repository\DemandeCongeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rh/export')]
#[IsGranted('ROLE_RH')]
class ExportController extends AbstractController
{
    #[Route('/csv', name: 'rh_export_csv', methods: ['GET'])]
    public function csv(DemandeCongeRepository $repository): Response
    {
        $demandes = $repository->findAll();

        $response = new StreamedResponse(function () use ($demandes) {
            $handle = fopen('php://output', 'w+');
            fputcsv($handle, ['Employe', 'Type', 'Date debut', 'Date fin', 'Statut'], ';');

            foreach ($demandes as $demande) {
                fputcsv($handle, [
                    $demande->getEmploye()->getNomComplet(),
                    $demande->getTypeConge()->getLibelle(),
                    $demande->getDateDebut()->format('d/m/Y'),
                    $demande->getDateFin()->format('d/m/Y'),
                    $demande->getStatut()->label(),
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="demandes_conges.csv"');

        return $response;
    }
}