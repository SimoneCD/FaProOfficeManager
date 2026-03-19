<?php

namespace App\Form;

use App\Entity\Customer;
use App\Entity\Link;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('companyName', TextType::class, [
                'label' => 'c.companyName',
                'label_attr' => ['class' => 'bmd-label'],
                'attr' => ['class' => 'form-control'],
                'empty_data' => '',
                'required' => false,
            ])
            ->add('customerNumber', TextType::class, [
                'label' => 'w.customerNumber',
                'label_attr' => ['class' => 'bmd-label'],
                'attr' => ['class' => 'form-control'],
                'empty_data' => '',
                'required' => true,
            ])
            ->add('link', EntityType::class, [
                'label' => 'Verknüpfung zum Empfehlungslink, falls vorhanden',
                'class' => Link::class,
                'query_builder' => function (EntityRepository $er): QueryBuilder {
                    return $er->createQueryBuilder('l');
                },
                'attr' => ['class' => 'form-control'],
                'choice_label' => 'link',
                'required' => false,
            ])
            ->add('sex', ChoiceType::class, [
                'label' => 'w.salutation',
                'label_attr' => ['class' => 'bmd-label'],
               // 'label_attr' => ['class' => 'bmd-label'],
                'attr' => ['class' => 'form-control'],
                'choices' => [
                    '' => '',
                    'mr' => 'mr',
                    'ms' => 'ms',
                    'ma' => 'ma',
                ],
                'required' => false,
            ])
            ->add('name', TextType::class, [
                'label' => 'w.name',
                'label_attr' => ['class' => 'bmd-label'],
                'attr' => ['class' => 'form-control'],
                'empty_data' => '',
                'required' => false,
            ])
            ->add('title', TextType::class, [
                'label' => 'w.title',
                'label_attr' => ['class' => 'bmd-label'],
                'attr' => ['class' => 'form-control'],
                'empty_data' => '',
                'required' => false,
            ])
            ->add('surName', TextType::class, [
                'label' => 'w.surname',
                'label_attr' => ['class' => 'bmd-label'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'empty_data' => '',
            ])
            ->add('phone', TextType::class, [
                'label' => 'w.phone',
                'label_attr' => ['class' => 'bmd-label'],
                'attr' => ['class' => 'form-control'],
                'empty_data' => ' ',
                'required' => false,
            ])
            ->add('email', TextType::class, [
                'label' => 'w.email',
                'label_attr' => ['class' => 'bmd-label'],
                'attr' => ['class' => 'form-control'],
                'empty_data' => ' ',
                'required' => false,
            ])
            ->add('address', TextType::class, [
                'label' => 'w.address',
                'label_attr' => ['class' => 'bmd-label'],
                'attr' => ['class' => 'form-control'],
                'empty_data' => ' ',
                'required' => false,
            ])
            ->add('zip', TextType::class, [
                'label' => 'w.zip',
                'label_attr' => ['class' => 'bmd-label'],
                'attr' => ['class' => 'form-control'],
                'empty_data' => ' ',
                'required' => false,
            ])
            ->add('city', TextType::class, [
                'label' => 'w.city',
                'label_attr' => ['class' => 'bmd-label'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'empty_data' => 'Berlin',
            ])
            ->add('country', CountryType::class, [
                'label' => 'w.country',
                'label_attr' => ['class' => 'bmd-label'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'data' => 'DE',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
            'allow_extra_fields' => true,
            'csrf_protection' => false,
        ]);
    }
}
