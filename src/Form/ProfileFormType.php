<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(message: 'Name is required'),
                    new Length(max: 100, maxMessage: 'Name must be at most {{ limit }} characters'),
                ],
                'attr' => ['placeholder' => 'Your name', 'maxlength' => 100],
            ])
            ->add('timezone', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'UTC',
                'choices' => $this->getTimezoneChoices(),
                'label' => 'Timezone',
            ])
            ->add('kanjiClickAction', ChoiceType::class, [
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'choices' => [
                    'Show image icon on kanji card' => 'icon',
                    'Click kanji to search images' => 'auto',
                ],
                'label' => 'Kanji image search',
            ])
            ->add('readme', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Length(max: 5000, maxMessage: 'README must be at most {{ limit }} characters'),
                ],
                'attr' => ['rows' => 12, 'maxlength' => 5000, 'placeholder' => 'Write your profile README in Markdown...'],
                'label' => 'Profile README',
            ])
            ->add('avatar', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '2M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Upload a JPEG, PNG or WebP image',
                    ),
                ],
                'attr' => ['accept' => 'image/jpeg,image/png,image/webp'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => false,
        ]);
    }

    private function getTimezoneChoices(): array
    {
        $offsets = [
            'UTC' => 'UTC',
            'Pacific/Midway' => '(UTC-11) Midway',
            'Pacific/Honolulu' => '(UTC-10) Hawaii',
            'America/Anchorage' => '(UTC-09) Alaska',
            'America/Los_Angeles' => '(UTC-08) Pacific',
            'America/Denver' => '(UTC-07) Mountain',
            'America/Chicago' => '(UTC-06) Central',
            'America/New_York' => '(UTC-05) Eastern',
            'America/Halifax' => '(UTC-04) Atlantic',
            'America/St_Johns' => '(UTC-03:30) Newfoundland',
            'America/Sao_Paulo' => '(UTC-03) Brasília',
            'America/Noronha' => '(UTC-02) Fernando de Noronha',
            'Atlantic/Azores' => '(UTC-01) Azores',
            'Europe/London' => '(UTC+00) London',
            'Europe/Paris' => '(UTC+01) Paris',
            'Europe/Helsinki' => '(UTC+02) Helsinki',
            'Europe/Moscow' => '(UTC+03) Moscow',
            'Asia/Dubai' => '(UTC+04) Dubai',
            'Asia/Karachi' => '(UTC+05) Karachi',
            'Asia/Dhaka' => '(UTC+06) Dhaka',
            'Asia/Bangkok' => '(UTC+07) Bangkok',
            'Asia/Singapore' => '(UTC+08) Singapore',
            'Asia/Tokyo' => '(UTC+09) Tokyo',
            'Australia/Sydney' => '(UTC+10) Sydney',
            'Pacific/Noumea' => '(UTC+11) Noumea',
            'Pacific/Auckland' => '(UTC+12) Auckland',
        ];

        $choices = [];
        foreach ($offsets as $tz => $label) {
            $choices[$label] = $tz;
        }
        return $choices;
    }
}
