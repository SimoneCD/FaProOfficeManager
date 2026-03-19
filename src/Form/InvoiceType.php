<?php

namespace App\Form;

use App\Entity\Invoice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Invoice $data */
        $data = $options['data'];
        $builder
            ->add('text', TextareaType::class)
            ->add('pos0Text', TextType::class, [
                'label' => '000-Text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('pos1Text', TextType::class, [
                'label' => '001-Text',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('pos2Text', TextType::class, [
                'label' => '002-Text',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('pos3Text', TextType::class, [
                'label' => '003-Text',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('pos0Date', TextType::class, [
                'label' => '000-Date',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('pos1Date', TextType::class, [
                'label' => '001-Date',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('pos2Date', TextType::class, [
                'label' => '002-Date',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('pos3Date', TextType::class, [
                'label' => '003-Date',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('pos0Price', MoneyType::class, [
                'label' => '000-Price',
                'attr' => ['class' => 'posPrice form-control price0'],
            ])
            ->add('pos1Price', MoneyType::class, [
                'label' => '001-Price',
                'attr' => ['class' => 'posPrice form-control price1'],
                'required' => false,
            ])
            ->add('pos2Price', MoneyType::class, [
                'label' => '002-Price',
                'attr' => ['class' => 'posPrice form-control price2'],
                'required' => false,
            ])
            ->add('pos3Price', MoneyType::class, [
                'label' => '003-Price',
                'attr' => ['class' => 'posPrice form-control price3'],
                'required' => false,
            ])
            ->add('leistung', TextType::class, [
                'label' => 'p.kw',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
        ;
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            /** @var Invoice $invoice */
            $invoice = $event->getData();
            // dd($event);
            if (empty($invoice->getText())) {
                $context = $invoice->getContext();
                $offer = $invoice->getInvoiceOrder()->getOffer();
                if ($invoice->getType() === 'part' || $invoice->getType() === 'part-plus') {
                    if (!empty($context['partMailText'])) {
                        $invoice->setText($context['partMailText']);
                    } else {
                        $invoice->setText(str_replace('##offerNumber##', $offer->getNumber(), $offer->getSubCategory()->getPartInvoiceText()));
                    }
                } else {
                    if (!empty($context['finalMailText'])) {
                        $invoice->setText($context['finalMailText']);
                    } else {
                        $invoice->setText(str_replace('##offerNumber##', $offer->getNumber(), $offer->getSubCategory()->getInvoiceText()));
                    }
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Invoice::class,
            'allow_extra_fields' => true,
        ]);
    }
}
