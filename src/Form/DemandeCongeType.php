<?php

namespace App\Form;

use App\Entity\DemandeConge;
use App\Entity\TypeConge;
use App\Entity\User;
use App\Enum\PeriodeJournee;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DemandeCongeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $employesPossibles = $options['employes_possibles'];

        $builder
            ->add('employe', EntityType::class, [
                'class' => User::class,
                'choices' => $employesPossibles,
                'choice_label' => fn (User $u) => $u->getNomComplet(),
                'label' => 'Employé concerné',
                'disabled' => count($employesPossibles) <= 1,
            ])
            ->add('typeConge', EntityType::class, [
                'class' => TypeConge::class,
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('t')
                    ->where('t.actif = true')
                    ->orderBy('t.libelle', 'ASC'),
                'choice_label' => 'libelle',
                'label' => 'Type de congé',
            ])
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de début',
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de fin',
            ])
            ->add('periode', ChoiceType::class, [
                'choices' => [
                    'Journée complète' => PeriodeJournee::JOURNEE_COMPLETE,
                    'Matin uniquement' => PeriodeJournee::MATIN,
                    'Après-midi uniquement' => PeriodeJournee::APRES_MIDI,
                ],
                'label' => 'Période',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description / motif',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DemandeConge::class,
            'employes_possibles' => [],
        ]);
    }
}