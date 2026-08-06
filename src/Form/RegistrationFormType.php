<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank(message: 'Merci de renseigner votre prénom.'),
                    new Length(max: 100),
                ],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(message: 'Merci de renseigner votre nom.'),
                    new Length(max: 100),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(message: 'Merci de renseigner votre email.'),
                    new Email(message: 'Cette adresse email n\'est pas valide.'),
                ],
            ])
            ->add('rolePrincipal', ChoiceType::class, [
                'label' => 'Rôle',
                'mapped' => false,
                'choices' => [
                    'Employé' => 'ROLE_EMPLOYE',
                    'Responsable' => 'ROLE_RESPONSABLE',
                    'RH' => 'ROLE_RH',
                ],
                'constraints' => [
                    new NotBlank(message: 'Merci de sélectionner un rôle.'),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'Les deux mots de passe ne correspondent pas.',
                'first_options' => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Confirmation du mot de passe'],
                'constraints' => [
                    new NotBlank(message: 'Merci de choisir un mot de passe.'),
                    new Length(min: 8, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
