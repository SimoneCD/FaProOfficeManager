<?php

namespace App\Form;

use App\Entity\Link;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LinkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullname', TextType::class, [
                'label' => 'w.fullname',
                'label_attr' => ['class' => 'col-md-3'],
                'required' => true,
            ])
            ->add('email', TextType::class, [
                'label' => 'w.email',
                'label_attr' => ['class' => 'col-md-3'],
                'required' => true,
            ])
            ->add('notice', TextareaType::class, [
                'label' => 'Beschreibung',
                'label_attr' => ['class' => 'col-md-3'],
                'required' => false,
            ])
            ->add('amount', MoneyType::class, [
                'label' => 'Rabatt(pro.Person)',
                'label_attr' => ['class' => 'col-md-3'],
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Link::class,
        ]);
    }
}
