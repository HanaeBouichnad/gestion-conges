<?php

namespace App\Form;

use App\Entity\TypeConge;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TypeCongeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', TextType::class, ['label' => 'Type de congé'])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['rows' => 3],
                'help' => 'Courte phrase expliquant ce type de congé, affichée dans la liste.',
            ])
            ->add('pieceJointeObligatoire', CheckboxType::class, ['label' => 'Pièce jointe obligatoire', 'required' => false])
            ->add('soldeMax', NumberType::class, [
                'label' => 'Solde max par an (jours)',
                'required' => false,
                'scale' => 1,
                'html5' => true,
                'help' => 'Laisser vide si aucune limite ne s\'applique.',
            ])
            ->add('actif', CheckboxType::class, ['label' => 'Actif', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => TypeConge::class]);
    }
}