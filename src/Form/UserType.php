<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, ['label' => 'Prénom'])
            ->add('nom', TextType::class, ['label' => 'Nom'])
            ->add('email', EmailType::class, ['label' => 'Email'])
            ->add('rolePrincipal', ChoiceType::class, [
                'label' => 'Rôle',
                'mapped' => false,
                'choices' => [
                    'Employé' => 'ROLE_EMPLOYE',
                    'Responsable' => 'ROLE_RESPONSABLE',
                    'RH' => 'ROLE_RH',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
            ])
            ->add('responsable', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'nomComplet',
                'label' => 'Responsable hiérarchique',
                'required' => false,
                'placeholder' => 'Aucun',
            ])
            ->add('photoUrl', UrlType::class, [
                'label' => 'URL de la photo de profil',
                'required' => false,
            ])
            ->add('actif', CheckboxType::class, ['label' => 'Compte actif', 'required' => false]);

        if ($options['is_creation']) {
            $builder->add('password', PasswordType::class, [
                'mapped' => false,
                'label' => 'Mot de passe',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_creation' => false,
        ]);
    }
}
