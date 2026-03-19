<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Inquiry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 *
 */
class InquiryCustomerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $date = new \DateTime();
        $builder
            ->add('customer', CustomerType::class, [
                'label' => 'w.customer',
                'label_attr' => ['class' => 'control-label col-md-3'],
                'by_reference' => true,
                'required' => true,
            ])
            ->add('notice', TextareaType::class, [
                'label' => 'w.notice',
                'label_attr' => ['class' => 'control-label col-md-3'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'inquiry';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Inquiry::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
    }
}
