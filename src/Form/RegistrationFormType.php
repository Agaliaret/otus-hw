<?php

namespace OtusHw\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a username',
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Your username should be at least {{ limit }} characters',
                        'max' => 50,
                        'maxMessage' => 'Your username should be at most {{ limit }} characters',
                    ]),
                ]
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'options' => ['attr' => ['class' => 'password-field']],
                'first_options'  => ['label' => 'Password'],
                'second_options' => ['label' => 'Repeat Password'],
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a name',
                    ]),
                    new Length([
                        'max' => 150,
                        'maxMessage' => 'Your name should be at most {{ limit }} characters',
                    ]),
                ]
            ])
            ->add('surname', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a surname',
                    ]),
                    new Length([
                        'max' => 150,
                        'maxMessage' => 'Your surname should be at most {{ limit }} characters',
                    ]),
                ]
            ])
            ->add('age', IntegerType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your age',
                    ]),
                    new GreaterThanOrEqual([
                        'value' => 18,
                        'message' => 'You must be at least {{ compared_value }} years old to register',
                    ]),
                    new LessThanOrEqual([
                        'value' => 150,
                        'message' => 'Holy shit! You\'re too old, man!',
                    ]),
                ]
            ])
            ->add('gender', ChoiceType::class, [
                'choices' => ['Male' => 'male', 'Female' => 'female'],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please choose gender',
                    ]),
                ]
            ])
            ->add('interests', CollectionType::class, [
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'entry_options' => ['label' => false],
            ])
            ->add('city', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a city',
                    ]),
                    new Length([
                        'max' => 150,
                        'maxMessage' => 'City name should be at most {{ limit }} characters',
                    ]),
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([

        ]);
    }
}
