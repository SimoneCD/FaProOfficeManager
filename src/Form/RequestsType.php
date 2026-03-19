<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Document;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RequestsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextType::class, [
                'label' => 'w.description',
                'label_attr' => ['class' => 'float-left'],
                'required' => false,
            ])
            ->add('filename', TextType::class, [
                'label' => 'Dateiname',
                'label_attr' => ['class' => 'float-left'],
                'required' => false,
            ])
            ->add('file', FileType::class, [
                'label' => 'Datei',
                'required' => false,
                'label_attr' => ['class' => 'float-left'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Document::class,
        ]);
    }
}
