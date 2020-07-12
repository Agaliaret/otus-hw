<?php

namespace OtusHw\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

class UserSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 150,
                        'maxMessage' => 'Too long name (max {{ limit }} characters allowed)',
                    ]),
                ]
            ])
            ->add('surname', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 150,
                        'maxMessage' => 'Too long surname (max {{ limit }} characters allowed)',
                    ]),
                ]
            ])
            ->add('ageFrom', IntegerType::class, [
                'required' => false,
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => 1,
                        'message' => 'Please enter valid age',
                    ]),
                    new LessThanOrEqual([
                        'value' => 150,
                        'message' => 'Please enter valid age',
                    ]),
                ]
            ])
            ->add('ageTo', IntegerType::class, [
                'required' => false,
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => 1,
                        'message' => 'Please enter valid age',
                    ]),
                    new LessThanOrEqual([
                        'value' => 150,
                        'message' => 'Please enter valid age',
                    ]),
                ]
            ])
            ->add('gender', ChoiceType::class, [
                'choices' => ['Male' => 'male', 'Female' => 'female', 'Any' => null],
                'expanded' => true,
                'multiple' => false,
                'data' => null
            ])
            ->add('city', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 150,
                        'maxMessage' => 'City name must be at most {{ limit }} characters',
                    ]),
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
