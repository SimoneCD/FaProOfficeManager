<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\ProjectTeam;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectTeamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'w.name',
                'label_attr' => ['class' => 'form-label col-sm-2'],
            ])
            ->add('category', null, [
                'label' => 'w.category',
                'label_attr' => ['class' => 'form-label col-sm-2'],
                'required' => false,
            ])
            ->add('partner', null, [
                'label' => 'w.partner', 'required' => false,
                'label_attr' => ['class' => 'form-label col-sm-2'],
            ])
            ->add('users', null, [
                'label' => 'w.employees',
                'label_attr' => ['class' => 'form-label col-sm-2'],
                'required' => false,
            ])

            ->add('isDefault', CheckboxType::class, [
                'label' => 'Default',
                'attr' => ['class' => 'form-control-o'],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProjectTeam::class,
        ]);
    }
}
