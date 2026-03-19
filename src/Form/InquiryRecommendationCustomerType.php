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
class InquiryRecommendationCustomerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // $date = new \DateTime();
        $builder
            ->add('customer', CustomerRequiredType::class, [
                'label' => 'w.customer',
                'label_attr' => ['class' => 'd-none control-label col-md-3'],
                'by_reference' => true,
                'required' => true,
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
