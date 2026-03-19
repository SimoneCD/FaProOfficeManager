<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Customer;
use App\Entity\Invoice;
use App\Entity\Offer;
use App\Entity\OfferAnswers;
use App\Entity\OfferCategory;
use App\Entity\OfferOption;
use App\Entity\OfferSubCategory;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\ProductOrder;
use App\Entity\ProductSubCategory;
use App\Entity\User;
use App\Service\PriceService;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class StorageTwigExtension extends AbstractExtension
{
    protected $storage = [];

    protected $entityManager;

    protected $priceService;

    protected string $pdfPath;

    public function __construct(
        EntityManagerInterface $entityManager,
        PriceService $priceService,
        string $appPdfPath,
        string $hdDir,
    ) {
        $this->entityManager = $entityManager;
        $this->priceService = $priceService;
        $this->pdfPath = $appPdfPath;
        $this->hdDir = $hdDir;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('save', $this->save(...), ['needs_context' => true]),
            new TwigFunction('restore', $this->restore(...), ['needs_context' => true]),
            new TwigFunction('calculatePrice', $this->calculatePrice(...)),
            new TwigFunction('calculateNettoPrice', $this->calculateNettoPrice(...)),
            new TwigFunction('calculateTax', $this->calculateTax(...)),
            new TwigFunction('calculate70Netto', $this->calculate70Netto(...)),
            new TwigFunction('calculate70Brutto', $this->calculate70Brutto(...)),
            new TwigFunction('calculateAnzOffers', $this->calculateAnzOffers(...)),
            new TwigFunction('getOff', $this->getOff(...)),
            new TwigFunction('getIn', $this->getIn(...)),
            new TwigFunction('getOfferTitle', $this->getOfferTitle(...)),
            new TwigFunction('getOfferName', $this->getOfferName(...)),
            new TwigFunction('getTopUsers', $this->getTopUsers(...)),
            new TwigFunction('getMontageUsers', $this->getMontageUsers(...)),
            new TwigFunction('getAnswer', $this->getAnswer(...)),
            new TwigFunction('getProduct', $this->getProduct(...)),
            new TwigFunction('getHauptproducts', $this->getHauptproducts(...)),
            new TwigFunction('getProductCategories', $this->getProductCategories(...)),
            new TwigFunction('getProductCategory', $this->getProductCategory(...)),
            new TwigFunction('getProductSubCategories', $this->getProductSubCategories(...)),
            new TwigFunction('getOfferCategories', $this->getOfferCategories(...)),
            new TwigFunction('getImgContent', $this->getImgContent(...)),
            new TwigFunction('generateQrCode', $this->generateQrCode(...)),
            new TwigFunction('getStatusNumber', $this->getStatusNumber(...)),
        ];
    }

    public function getStatusNumber(string $status): int
    {
        $array = [
            'storno' => 0,
            'call' => 1,
            'call-plus' => 1,
            'estimate' => 2,
            'besichtigung' => 3,
            'open' => 4,
            'gesendet' => 5,
            'bestaetigt' => 6,
            'rechnungPartSend' => 7,
            'rechnungPartEingang' => 8,
            'work' => 9,
            'done' => 10,
            'rechnungEingangSend' => 11,
            'rechnungEingang' => 12,
            'archive' => 13,
        ];

        return $array[$status];
    }

    public function generateQrCode(string $route, int $number)
    {
        $writer = new PngWriter();
        $qrCode = QrCode::create($route)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
            ->setSize(120)
            ->setMargin(0)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));
        $logo = Logo::create($this->hdDir.'/logo/round.png')
            ->setResizeToWidth(60);
        $label = Label::create(''); // ->setFont(new NotoSans(8));
        //
        //        $qrCodes = [];
        //        $qrCodes['img'] = $writer->write($qrCode, $logo)->getDataUri();
        //        $qrCodes['simple'] = $writer->write(
        //            $qrCode,
        //            null,
        //            $label->setText('Simple')
        //        )->getDataUri();
        //
        //        $qrCode->setForegroundColor(new Color(255, 0, 0));
        //        $qrCodes['changeColor'] = $writer->write(
        //            $qrCode,
        //            null,
        //            $label->setText('Color Change')
        //        )->getDataUri();
        //
        //        $qrCode->setForegroundColor(new Color(0, 0, 0))->setBackgroundColor(new Color(255, 0, 0));
        //        $qrCodes['changeBgColor'] = $writer->write(
        //            $qrCode,
        //            null,
        //            $label->setText('Background Color Change')
        //        )->getDataUri();

        $text = ''.(Customer::CUSTOMER_START + $number);
        $qrCodes['withImage'] = $writer->write(
            $qrCode,
            $logo,
            $label->setText($text)->setFont(new NotoSans(20))
        )->getDataUri();

        return $qrCodes['withImage'];
    }

    public function save($context, string $name): void
    {
        $this->storage[$name] = $context[$name];
    }

    public function restore(&$context, string $name): void
    {
        $context[$name] = $this->storage[$name];
    }

    public function getOff(int $offerId): Offer
    {
        return $this->entityManager->getRepository(Offer::class)->find($offerId);
    }

    public function getIn(int $invoiceId): Invoice
    {
        return $this->entityManager->getRepository(Invoice::class)->find($invoiceId);
    }

    public function calculateNettoPrice(int $offerId): ?float
    {
        if ($offerId instanceof Offer) {
            $offer = $offerId;
        } else {
            $offer = $this->entityManager->getRepository(Offer::class)->find($offerId);
        }

        return $this->priceService->calculateNettoPrice($offer);
    }

    public function getOfferTitle(int $offerId, bool $upper = true): string
    {
        $offer = $this->entityManager->getRepository(Offer::class)->find($offerId);
        $options = $offer->getOption();
        if ($options instanceof OfferOption) {
            $context = $options->getContext();
            if (!empty($context) && !empty($context['header']['title'])) {
                return $options->getContext()['header']['title'];
            }
        }

        return $offer->getSubCategory() instanceof OfferSubCategory ? $offer->getSubCategory()->getName() : '';
    }

    public function getOfferName(int $offerId, bool $upper = false, bool $full = false): string
    {
        /** @var Offer $offer */
        $offer = $this->entityManager->getRepository(Offer::class)->find($offerId);
        $options = $offer->getOption();
        if ($options instanceof OfferOption) {
            $context = $options->getContext();
            if (!empty($context) && !empty($context['header']['text'])) {
                return $options->getContext()['header']['text'];
            }
        }

        $product = $offer->getWallboxProduct();
        $name = '';
        $mainProduct = $product instanceof Product;
        $valueName = '';
        if ($mainProduct) {
            $valueName = $product->getValueName() ?? '';
            $name = ($offer->getKw() * ($offer->getAmount() ?? 1)).' '.$product->getName();
        } elseif ($options instanceof OfferOption) {
            $context = $offer->getOption()->getContext();
            if (!empty($context) && isset($context['header'])) {
                $name = $context['header']['text'];
            } elseif (!empty($offer->getSubTitle())) {
                $name = $offer->getSubTitle();
            } elseif ($mainProduct === true) {
                $name = ($offer->getKw() * ($offer->getAmount() ?? 1)).' '.$product->getName();
            }
        } elseif (!empty($offer->getSubTitle())) {
            $name = $offer->getSubTitle();
        } elseif ($mainProduct === true) {
            $name = ($offer->getKw() * ($offer->getAmount() ?? 1)).' '.$product->getName();
        }

        if ($full) {
            if (!empty($offer->getKw())) {
                $name .= ' | '.($offer->getKw() * ($offer->getAmount() ?? 1)).' '.$valueName;
            } elseif (!empty($offer->getWallboxProduct()) && !empty($offer->getWallboxProduct()->getKw())) {
                $name .= ' | '.($offer->getWallboxProduct()->getKw() * ($offer->getAmount() ?? 1)).' '.$valueName;
            }
        }

        return $name;
    }

    private function getCustomerTitle(Offer $offer): string
    {
        return $offer->getSubTitle() ?? '';
    }

    private function getTopUsers(): ?array
    {
        return $this->entityManager->getRepository(User::class)->findBy([
            'status' => true,
        ]);
    }

    private function getMontageUsers(): ?array
    {
        return $this->entityManager->getRepository(User::class)->findMontageUser();
    }

    private function getAnswer(int $id): string
    {
        $answer = $this->entityManager->getRepository(OfferAnswers::class)->find($id);

        if ($answer instanceof OfferAnswers) {
            return $answer->getName();
        }

        return 'answer is deleted';
    }

    /**
     * @return Product|string
     */
    private function getProduct(int|string $id, ?bool $object = false)
    {
        if ($id !== 0) {
            $product = $this->entityManager->getRepository(Product::class)->find($id);
            if ($product instanceof Product) {
                if ($object) {
                    return $product;
                }

                return $product->getProductNumber().'|'.$product->getName();
            }

            return 'deleted produc '.$id;
        }

        return '';
    }

    private function getHauptproducts(ProductSubCategory $psc): ?array
    {
        return $this->entityManager->getRepository(Product::class)->findMainProductsByCategory($psc);
    }

    private function getProductCategories(): ?array
    {
        return $this->entityManager->getRepository(ProductCategory::class)->findAll();
    }

    private function getProductSubCategories(): ?array
    {
        return $this->entityManager->getRepository(ProductSubCategory::class)->findAll();
    }

    private function getProductCategory(int $id): ?ProductCategory
    {
        return $this->entityManager->getRepository(ProductCategory::class)->find($id);
    }

    private function getOfferCategories(): ?array
    {
        return $this->entityManager->getRepository(OfferCategory::class)->findAll();
    }

    /**
     * @return float|int
     */
    public function calculate70Netto(int $offerId, ?int $percent = 70)
    {
        $offer = $this->entityManager->getRepository(Offer::class)->find($offerId);

        return $this->priceService->calculate70Netto($offer, $percent ?? $offer->getOption()->getInvoicePercent());
    }

    /**
     * @return float|int
     */
    public function calculate70Brutto(int $offerId, ?int $percent = 70)
    {
        $offer = $this->entityManager->getRepository(Offer::class)->find($offerId);

        return $this->priceService->calculate70Brutto($offer, $percent ?? $offer->getOption()->getInvoicePercent());
    }

    /**
     * @return float|int
     */
    public function calculate70Tax(int $offerId, ?int $percent = 70)
    {
        $offer = $this->entityManager->getRepository(Offer::class)->find($offerId);

        return $this->priceService->calculate70Tax($offer, $percent ?? $offer->getOption()->getInvoicePercent());
    }

    /**
     * @return float|int
     */
    public function calculateTax(int $offerId)
    {
        $offer = $this->entityManager->getRepository(Offer::class)->find($offerId);

        return $this->priceService->calculateTax($offer);
    }

    /**
     * @return float|int|mixed|null
     */
    public function calculateAnzOffers(int $offerId): int
    {
        $offer = $this->entityManager->getRepository(Offer::class)->find($offerId);
        $customer = $offer->getCustomer();

        return $customer->getOffers()->count();
    }

    /**
     * @return float|int|mixed|null
     */
    public function calculatePrice(int $offerId)
    {
        $offer = $this->entityManager->getRepository(Offer::class)->find($offerId);

        return $this->priceService->calculatePrice($offer);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'storage';
    }

    public function getImgContent(string $fileName): string
    {
        if (str_starts_with($fileName, '/')) {
            $fileName = substr($fileName, 1);
        }
        $fn = $this->pdfPath.'/'.$fileName;
        $c = $this->curl_file_get_contents($fn);
        if (!$c) {
            $c = $this->curl_file_get_contents($this->pdfPath.'/app/img/1x1.png');

            if (!$c) {
                return '';
            }

            return 'data:image/png;base64,'.base64_encode($c);
        }

        return 'data:image/png;base64,'.base64_encode($c);
    }

    private function curl_file_get_contents($url)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        #curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, 'ZOE Solar/1.0');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $contents = curl_exec($curl);
        curl_close($curl);

        return $contents;
    }
}
