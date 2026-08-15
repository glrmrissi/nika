<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class TwoFactorVerifyFormType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Authentication code',
                'attr' => [
                    'placeholder' => '000000',
                    'autocomplete' => 'off',
                    'inputmode' => 'numeric',
                    'maxlength' => 6,
                ],
                'constraints' => [
                    new NotBlank(message: 'Enter the code'),
                    new Length(min: 6, max: 6, exactMessage: 'Code must be 6 digits'),
                    new Regex(pattern: '/^\d{6}$/', message: 'Numbers only'),
                ],
            ]);
    }
}
