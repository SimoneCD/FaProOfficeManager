<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Invoice;
use App\Entity\Offer;
use App\Service\PriceService;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DashTwigExtension extends AbstractExtension
{
    protected array $storage = [];

    protected EntityManagerInterface $entityManager;

    protected PriceService $priceService;

    public function __construct(
        EntityManagerInterface $entityManager,
        PriceService $priceService
    ) {
        $this->entityManager = $entityManager;
        $this->priceService = $priceService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('openOffers', $this->openOffers(...)),
            new TwigFunction('calledOffers', $this->calledOffers(...)),
            new TwigFunction('confirmedOffers', $this->confirmedOffers(...)),
            new TwigFunction('payedRestBills', $this->payedRestBills(...)),
            new TwigFunction('payedPartBills', $this->payedPartBills(...)),
            new TwigFunction('payedPartPlusBills', $this->payedPartPlusBills(...)),
            new TwigFunction('unpayedBills', $this->unpayedBills(...)),
        ];
    }

    public function openOffers(): int
    {
        $offers = $this->entityManager->getRepository(Offer::class)->findOpenOffers();

        return count($offers);
    }

    public function calledOffers(): int
    {
        $offers = $this->entityManager->getRepository(Offer::class)->findCalledOffers();

        return count($offers);
    }

    public function confirmedOffers(): int
    {
        $offers = $this->entityManager->getRepository(Offer::class)->findSendedOffers();

        return count($offers);
    }

    public function payedPartBills($date, $formatted = true): string|float
    {
        $date = new \DateTime($date.'-01');
        $partPrice = 0;
        $bills = $this->entityManager->getRepository(Invoice::class)->findMonthInvoice($date, 'part');
        foreach ($bills as $c) {
            $partPrice += $c->getPos0Price() ?? 0;
            $partPrice += $c->getPos1Price() ?? 0;
            $partPrice += $c->getPos2Price() ?? 0;
            $partPrice += $c->getPos3Price() ?? 0;
        }
        if ($formatted) {
            return number_format($partPrice, 2, ',', '.');
        } else {
            return $partPrice;
        }
    }

    public function payedPartPlusBills($date, $formatted = true): string|float
    {
        $date = new \DateTime($date.'-01');
        $partPrice = 0;
        $bills = $this->entityManager->getRepository(Invoice::class)->findMonthInvoice($date, 'part-plus', false);

        foreach ($bills as $c) {
            $partPrice += $c->getPos0Price() ?? 0;
            $partPrice += $c->getPos1Price() ?? 0;
            $partPrice += $c->getPos2Price() ?? 0;
            $partPrice += $c->getPos3Price() ?? 0;
        }
        if ($formatted) {
            return number_format($partPrice, 2, ',', '.');
        } else {
            return $partPrice;
        }
    }

    public function unpayedBills($date): string|float
    {
        $date = new \DateTime($date.'-01');
        $price = 0;
        $bills = $this->entityManager->getRepository(Invoice::class)->findMonthInvoice($date, false, false);
        foreach ($bills as $c) {
            $price += $c->getPos0Price() ?? 0;
            $price += $c->getPos1Price() ?? 0;
            $price += $c->getPos2Price() ?? 0;
            $price += $c->getPos3Price() ?? 0;
        }

        return $price;
    }

    public function payedRestBills($date, $formatted = false): string|float
    {
        $date = new \DateTime($date.'-01');
        $partPrice = 0;
        $restPrice = 0;
        $repo = $this->entityManager->getRepository(Invoice::class);
        $billsRest = $repo->findMonthInvoice($date, 'rest');

        /** @var Invoice $c */
        foreach ($billsRest as $c) {
            $restPrice += $c->getPos0Price() ?? 0;
            $restPrice += $c->getPos1Price() ?? 0;
            $restPrice += $c->getPos2Price() ?? 0;
            $restPrice += $c->getPos3Price() ?? 0;
        }

        if ($formatted) {
            return number_format($restPrice, 2, ',', '.');
        } else {
            return $restPrice;
        }
    }

    public function getName(): string
    {
        return 'dash';
    }
}
