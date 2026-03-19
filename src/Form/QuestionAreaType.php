<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\QuestionArea;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionAreaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'w.name',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('sort', null, [
                'label' => 'w.sort',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('questions', null, [
                'label' => 'w.questions',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuestionArea::class,
        ]);
    }
}
