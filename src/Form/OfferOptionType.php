<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\OfferOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferOptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        new \DateTime();
        $builder
            ->add('solar', null, ['label' => 'o.taxFree',
                'attr' => ['class' => 'form-control auto_fill'],
                'label_attr' => ['class' => 'switcher'],
                'required' => false,
            ])
            ->add('outletFuseMeter', null, ['label' => 'osc.quest.q.answerTypes.length.name',
                'attr' => ['class' => 'form-control'],
                'label_attr' => ['class' => 'bmd-label-floating auto_fill'],
                'empty_data' => '0',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OfferOption::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
    }
}
