<?php

namespace App\Form;

use App\Entity\Offer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 *
 */
class OfferEmptyWallboxType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('kw', NumberType::class, [
                'label' => 'product.offer.kw',
                'attr' => ['list' => 'kw', 'class' => 'form-control form_done_d'],
                'label_attr' => ['class' => 'bmd-label-floating no_auto'],
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offer::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
    }
}
