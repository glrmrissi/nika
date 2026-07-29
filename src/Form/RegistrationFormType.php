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
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['placeholder' => 'Your name', 'maxlength' => 100],
                'constraints' => [
                    new NotBlank(message: 'Enter your name'),
                    new Length(max: 100, maxMessage: 'Name must be at most {{ limit }} characters'),
                ],
            ])
            ->add('email', EmailType::class, [
                'attr' => ['placeholder' => 'you@example.com', 'maxlength' => 180],
                'constraints' => [
                    new NotBlank(message: 'Enter your email'),
                    new Email(message: 'Invalid email'),
                    new Length(max: 180, maxMessage: 'Email must be at most {{ limit }} characters'),
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'Password'],
                'second_options' => ['label' => 'Confirm password'],
                'constraints' => [
                    new NotBlank(message: 'Enter a password'),
                    new Length(min: 8, max: 128),
                    new Regex(
                        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
                        message: 'Password must contain uppercase, lowercase and numbers',
                    ),
                ],
            ])
            ->add('timezone', ChoiceType::class, [
                'choices' => [
                    'Africa/Cairo (UTC+2)' => 'Africa/Cairo',
                    'Africa/Johannesburg (UTC+2)' => 'Africa/Johannesburg',
                    'Africa/Lagos (UTC+1)' => 'Africa/Lagos',
                    'America/Argentina/Buenos_Aires (UTC-3)' => 'America/Argentina/Buenos_Aires',
                    'America/Chicago (UTC-6)' => 'America/Chicago',
                    'America/Denver (UTC-7)' => 'America/Denver',
                    'America/Los_Angeles (UTC-8)' => 'America/Los_Angeles',
                    'America/New_York (UTC-5)' => 'America/New_York',
                    'America/Sao_Paulo (UTC-3)' => 'America/Sao_Paulo',
                    'Asia/Dubai (UTC+4)' => 'Asia/Dubai',
                    'Asia/Hong_Kong (UTC+8)' => 'Asia/Hong_Kong',
                    'Asia/Kolkata (UTC+5:30)' => 'Asia/Kolkata',
                    'Asia/Seoul (UTC+9)' => 'Asia/Seoul',
                    'Asia/Shanghai (UTC+8)' => 'Asia/Shanghai',
                    'Asia/Tokyo (UTC+9)' => 'Asia/Tokyo',
                    'Australia/Sydney (UTC+11)' => 'Australia/Sydney',
                    'Europe/Berlin (UTC+1)' => 'Europe/Berlin',
                    'Europe/London (UTC+0)' => 'Europe/London',
                    'Europe/Madrid (UTC+1)' => 'Europe/Madrid',
                    'Europe/Moscow (UTC+3)' => 'Europe/Moscow',
                    'Europe/Paris (UTC+1)' => 'Europe/Paris',
                    'Pacific/Auckland (UTC+13)' => 'Pacific/Auckland',
                    'Pacific/Honolulu (UTC-10)' => 'Pacific/Honolulu',
                    'UTC (UTC+0)' => 'UTC',
                ],
                'label' => 'Timezone',
                'placeholder' => 'Choose your timezone',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => false,
        ]);
    }
}
