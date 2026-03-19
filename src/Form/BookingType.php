<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Booking;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Offer Appointments.
 */
class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', ChoiceType::class, [
                'label' => 'book.what',
                'attr' => ['class' => 'form-control'],
                'choices' => [
                    'Anrufen' => 'Anrufen',
                    'Besichtigung' => 'Besichtigung',
                    'Montage/Installation' => 'Montage/Installation',
                    'Terminvorschlag' => 'Terminvorschlag',
                    'Sonstiges' => 'Sonstiges',
                ],
                'required' => true,
            ])
            ->add('beginAt', DateTimeType::class, [
                'label' => 'Terminstart',
                'format' => 'dd.MM.yyyy HH:mm',
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'form-control termin'],
            ])
            ->add('endAt', DateTimeType::class, [
                'label' => 'Terminende',
                'format' => 'dd.MM.yyyy HH:mm',
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'form-control termin'],
            ])
            ->add('notice', TextareaType::class, [
                'label' => 'book.info',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
        ]);
    }
}
