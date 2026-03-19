<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Inquiry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InquiryEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('refererFrom', TextType::class, [
                'label' => 'w.refererFrom',
                'label_attr' => ['class' => 'control-label col-md-3'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('notice', TextareaType::class, [
                'label' => 'iq.notice',
                'label_attr' => ['class' => 'control-label col-md-3'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Inquiry::class,
            'allow_extra_fields' => true,
            'csrf_protection' => false,
        ]);
    }
}
