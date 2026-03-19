<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\Offer;
use App\Entity\OfferOption;
use App\Entity\Product;

class PriceService
{
    public function calculateInvoicePrice(Invoice $invoice): ?float
    {
        $price1 = floatval($invoice->getPos0Price());
        $price2 = floatval($invoice->getPos1Price());
        $price3 = floatval($invoice->getPos2Price());
        $price4 = floatval($invoice->getPos3Price());
        $offer = $invoice->getInvoiceOrder()->getOffer();
        $price = ($price1 + $price2 + $price3 + $price4);
        if ($offer->getOption()->isSolar() === false) {
            $price = round(($offer->getTax() * $price / 100) + $price, 2);
        }

        return $price - $offer->getRabat();
    }

    public function calculateNettoPrice(Offer $offer): ?float
    {
        $price = 0;
        foreach ($offer->getOfferItems() as $item) {
            $price += $item->getPrice() * $item->getAmount();
        }
        if ($offer->getWallboxProduct() instanceof Product) {
            $price = $this->getWallboxPrice($offer, $price);
        }

        return $price - $offer->getRabat();
    }

    public function calculate70Netto(Offer $offer, int $percent = null): float
    {
        $price = 0;
        foreach ($offer->getOfferItems() as $item) {
            $price += $item->getPrice() * $item->getAmount();
        }
        if ($offer->getWallboxProduct() instanceof Product) {
            $price = $this->getWallboxPrice($offer, $price);
        }
        $price -= $offer->getRabat();

        return round(($percent !== null ? $percent : $offer->getOption()->getInvoicePercent()) * $price / 100, 2);
    }

    public function calculate70Brutto(Offer $offer, int $percent = null): float
    {
        $price = 0;
        foreach ($offer->getOfferItems() as $item) {
            $price += $item->getPrice() * $item->getAmount();
        }
        if ($offer->getWallboxProduct() instanceof Product) {
            $price = $this->getWallboxPrice($offer, $price);
        }
        $price -= $offer->getRabat();
        $price = ($percent ?? $offer->getOption()->getInvoicePercent()) * $price / 100;

        if ($offer->getOption()->isSolar()) {
            return round($price, 2);
        }

        return round(($offer->getTax() * $price / 100) + $price, 2);
    }

    public function calculate70Tax(Offer $offer, ?int $percent = 70): float
    {
        $price = 0;
        foreach ($offer->getOfferItems() as $item) {
            $price += $item->getPrice() * $item->getAmount();
        }
        if ($offer->getWallboxProduct() instanceof Product) {
            $price = $this->getWallboxPrice($offer, $price);
        }
        $price -= $offer->getRabat();
        $price = $offer->getOption()->getInvoicePercent() * $price / 100;

        if ($offer->getOption()->isSolar()) {
            return 0;
        }

        return $offer->getTax() * $price / 100;
    }

    public function calculateTax(Offer $offer): float
    {
        $price = 0;
        foreach ($offer->getOfferItems() as $item) {
            $price += $item->getPrice() * $item->getAmount();
        }
        if ($offer->getWallboxProduct() instanceof Product) {
            $price = $this->getWallboxPrice($offer, $price);
        }
        $price -= $offer->getRabat();

        return $offer->getTax() * $price / 100;
    }

    public function calculatePrice(Offer $offer): float
    {
        $price = 0;
        foreach ($offer->getOfferItems() as $item) {
            if (!empty($item->getPrice()) && !empty($item->getAmount())) {
                // dump($price + ($item->getPrice()*$item->getAmount()));
                $price += $item->getPrice() * $item->getAmount();
            }
        }
        if ($offer->getWallboxProduct() instanceof Product) {
            $price = $this->getWallboxPrice($offer, $price);
        }
        $price -= $offer->getRabat();

        if ($offer->getOption() instanceof OfferOption and $offer->getOption()->isSolar()) {
            return $price;
        }

        return ($offer->getTax() * $price / 100) + $price;
    }

    private function getWallboxPrice(Offer $offer, float $price)
    {
        if (!empty($offer->getWallboxPrice())) {
            $price += $offer->getWallboxPrice() * $offer->getAmount();
        } else {
            $price += $offer->getWallboxProduct()->getPrice() * $offer->getAmount();
        }

        return $price;
    }
}
