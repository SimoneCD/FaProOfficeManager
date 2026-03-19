<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ActionLog;
use App\Entity\Booking;
use App\Entity\Customer;
use App\Entity\Inquiry;
use App\Entity\Offer;
use App\Entity\OfferOption;
use App\Entity\Order;
use App\Service\PHPMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BaseController extends AbstractController
{
    use CacheTrait;

    protected HttpClientInterface $client;

    protected EntityManagerInterface $em;

    protected PHPMailerService $mailerService;

    protected TranslatorInterface $translator;

    protected FilesystemAdapter $fa;

    protected string $subdomain;

    public function __construct(EntityManagerInterface $em, HttpClientInterface $client, PHPMailerService $mailerService, TranslatorInterface $translator, string $subdomain)
    {
        $this->client = $client;
        $this->mailerService = $mailerService;
        $this->translator = $translator;
        $this->em = $em;
        $this->subdomain = $subdomain;
        $this->fa = new FilesystemAdapter($subdomain.'_caches', 3600, '../var/tmp');
    }

    /**
     * Loggt nach 1 Stunde inaktivität (nicht Admins) aus.
     *
     * @return false|string
     */
    protected function checkUserTime(Request $request)
    {
        $session = $request->getSession();
        if (time() - $session->getMetadataBag()->getLastUsed() >= 1800) {
            $session->invalidate();

            return $this->generateUrl('security_logout');
        }

        return false;
    }

    protected function deleteOffer(Offer $offer, string $hdDir)
    {
        $em = $this->em;
        foreach ($offer->getOfferItems() as $o) {
            $o->setOffer(null);
            $o->setItem(null);
            $em->remove($o);
        }
        $em->flush();
        /** @var Booking $b */
        foreach ($offer->getBookings() as $b) {
            $b->setOffer(null);
            // $b->setCustomer(null);
            $em->persist($b);
        }
        $em->flush();
        $option = $offer->getOption();
        $option->setOffer(null);
        $offer->setOption(null);
        $offer->setCustomer(null);
        $offer->setCustomer(null);
        $offer->setUser(null);
        $offer->setWallboxProduct(null);
        $this->removeOfferImages($hdDir, $offer);
        $order = $offer->getOrder();
        if ($order instanceof Order) {
            $offer->setOrder(null);
            $em->persist($offer);
            $order->setOffer(null);
            if (0 !== $order->getInvoices()->count()) {
                $this->removeOrderInvoices($order);
            }
            $em->remove($order);
            $em->flush();
        }
        if ($offer->getInquiry() instanceof Inquiry) {
            $in = $offer->getInquiry();
            $offer->setInquiry(null);
            $in->setOffer(null);
            $in->setCustomer(null);
            $in->setUser(null);
            $em->remove($in);
            $em->flush();
        }
        $em->remove($offer);
        $em->remove($option);
        $em->flush();
    }

    protected function deleteCustomer(Customer $customer)
    {
        $this->fa->delete('customer'.$customer->getId());
        $em = $this->em;
        $offers = $customer->getAllOffers();
        $inquiries = $customer->getInquiries();
        $bs = $customer->getBookings();
        foreach ($bs as $b) {
            $b->setCustomer(null);
            $b->setOffer(null);
            $em->remove($b);
        }
        $em->flush();
        foreach ($inquiries as $i) {
            $i->setUser(null);
            $i->setCustomer(null);
            $o = $i->getOffer();
            if ($o instanceof Offer) {
                $option = $o->getOption();
                if ($option instanceof OfferOption) {
                    $option->setOffer(null);
                    $em->remove($option);
                    $o->setOption(null);
                }
                $o->setInquiry(null);
                $em->persist($o);
            }
            $i->setOffer(null);
            $em->remove($i);
        }
        $em->flush();
        foreach ($offers as $offer) {
            foreach ($offer->getOfferItems() as $o) {
                $em->remove($o);
            }
            $offer->setCustomer(null);
            $offer->setProjectTeams(null);
            $offer->setUser(null);
            $offer->setMonteur(null);
            $offer->setWallboxProduct(null);
            $option = $offer->getOption();
            // $offer->setSauleProduct(null);
            $order = $offer->getOrder();
            if ($order instanceof Order) {
                $offer->setOrder(null);
                $em->persist($offer);
                $order->setOffer(null);
                $em->persist($order);
                if (0 !== $order->getInvoices()->count()) {
                    $this->removeOrderInvoices($order);
                }
                $em->remove($order);
                $em->flush();
            }
            $em->remove($offer);
            if ($option instanceof OfferOption) {
                $option->setOffer(null);
                $em->remove($option);
                $offer->setOption(null);
            }

            $em->flush();
        }
        $customer->setOffers(null);
        $em->remove($customer);
        $em->flush();
    }

    protected function addOptionContext(array $postContext, array &$offerContext)
    {
        foreach ($postContext as $key => $pc) {
            switch ($key) {
                case 'kv':
                    unset($offerContext['kv']);
                    if (isset($pc['name'])) {
                        foreach ($pc['name'] as $c => $en) {
                            $offerContext[$key]['name'][$c] = $en;
                        }
                    }
                    if (isset($pc['value'])) {
                        foreach ($pc['value'] as $c => $en) {
                            $offerContext[$key]['value'][$c] = $en;
                        }
                    }
                    break;
                case 'header':
                    unset($offerContext['header']);
                    if (isset($pc['title'])) {
                        $offerContext[$key]['title'] = $pc['title'];
                    }
                    if (isset($pc['text'])) {
                        $offerContext[$key]['text'] = $pc['text'];
                    }
                    break;
                case 'anrede':
                    unset($offerContext['anrede']);
                    if (isset($postContext['anrede'])) {
                        $offerContext['anrede'] = $pc;
                    }
                    break;
                case 'estimateAnrede':
                    unset($offerContext['estimateAnrede']);
                    if (isset($postContext['estimateAnrede'])) {
                        $offerContext['estimateAnrede'] = $pc;
                    }
                    break;
                case 'anredeTitle':
                    unset($offerContext['anredeTitle']);
                    if (isset($postContext['anredeTitle'])) {
                        $offerContext['anredeTitle'] = $pc;
                    }
                    break;
                case 'offerEmailText':
                    unset($offerContext['offerEmailText']);
                    if (isset($postContext['offerEmailText'])) {
                        $offerContext['offerEmailText'] = $pc;
                    }
                    break;
                case 'estimateEmailText':
                    unset($offerContext['estimateEmailText']);
                    if (isset($postContext['estimateEmailText'])) {
                        $offerContext['estimateEmailText'] = $pc;
                    }
                    break;
            }
        }
    }

    protected function addInvoiceContext(array $postContext, array &$invoiceContext)
    {
        foreach ($postContext as $key => $pc) {
            switch ($key) {
                case 'payed':
                case 'solar':
                case 'finalMailText':
                case 'mailText':
                case 'partMailText':
                    $invoiceContext[$key] = $pc;
                    break;
                case 'invoice':
                    if (isset($pc['name'])) {
                        unset($invoiceContext[$key]['name']);
                        foreach ($pc['name'] as $c => $en) {
                            $invoiceContext[$key]['name'][$c] = $en;
                        }
                    }
                    if (isset($pc['text'])) {
                        unset($invoiceContext[$key]['text']);
                        foreach ($pc['text'] as $c => $en) {
                            $invoiceContext[$key]['text'][$c] = $en;
                        }
                    }
                    break;
                default:
                    $invoiceContext[$key] = $pc;
            }
        }
    }

    protected function getStartParameter(Request $request): array
    {
        $search = $request->query->get('search') ?? null;
        $filter = $request->query->get('filter') ?? (empty($search) ? 'in' : 'all');

        if (empty($filter)) {
            $filter = 'in';
        }

        return [$search, $filter];
    }

    protected function getNextOfferNumber(Customer $customer): int
    {
        if (0 === $customer->getOffers()->count()) {
            return 1;
        }
        $offers = $this->em->getRepository(Offer::class)->findAllByOfferNumber($customer);
        $newnumber = 1;
        /** @var Offer $offer */
        foreach ($offers as $key => $offer) {
            $q = explode('.', $offer->getNumber());
            if ((int) $q[1] > $newnumber) {
                $newnumber = $q[1];
            }
        }

        return (int) $newnumber + 1;
    }

    protected function getRandomString($count)
    {
        $characters = 'abcdefghijkmnopqrstuvwxyz0123456789ABCDEFGHJKLMNOPQRSTUVWXYZ';
        $randstring = '';
        for ($i = 0; $i < $count; ++$i) {
            $randstring .= $characters[rand(0, strlen($characters))];
        }

        return $randstring;
    }

    protected function log(string $type, string $name, string $note, ?Customer $customer = null, ?Offer $offer = null, ?ActionLog $answer = null): Response
    {
        try {
            $actionLog = new ActionLog();
            $actionLog->setName($name);
            $actionLog->setCreatedAt(new \DateTime());
            $actionLog->setType($type);
            $actionLog->setUser($this->getUser());
            $actionLog->setNote($note);
            if ($offer instanceof Offer) {
                $actionLog->setOffer($offer);
            }
            if ($answer instanceof ActionLog) {
                $actionLog->setAnswer($answer);
            }
            if ($customer instanceof Customer) {
                $actionLog->setCustomer($customer);
            }
            $this->em->persist($actionLog);
            $this->em->flush();

            return $this->json(true);
        } catch (\Exception $exception) {
            return $this->json(false);
        }
    }

    /**
     * Googlemaps.
     */
    protected function getAddresCoordinates($address, $zip)
    {
        if (empty($address) && empty($zip)) {
            return json_encode([]);
        }
        try {
            // dd($address.', '.$zip);
            $response = $this->client->request(
                'GET',
                'http://api.positionstack.com/v1/forward', [
                    'query' => [
                        'access_key' => $_ENV['POSITIONSTACK_API_KEY'],
                        'query' => $address.', '.$zip,
                    ],
                ],
            );

            return $response->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function removeOfferImages(string $hdDir, Offer $offer)
    {
        $finder = new Finder();
        if (is_dir($hdDir.'/offers/'.$offer->getId())) {
            $files = $finder->files()->in($hdDir.'/offers/'.$offer->getId());
            $filesystem = new Filesystem();
            if ($files->hasResults()) {
                foreach ($files as $im) {
                    $filesystem->remove($im->getPathname());
                }
            }
        }

        if (is_dir($hdDir.'/uploads/'.$offer->getId())) {
            $files = $finder->files()->in($hdDir.'/uploads/'.$offer->getId());
            $filesystem = new Filesystem();
            if ($files->hasResults()) {
                foreach ($files as $im) {
                    $filesystem->remove($im->getPathname());
                }
                $filesystem->remove($hdDir.'/uploads/'.$offer->getId());
            }
        }
    }

    private function removeOrderInvoices(Order $order)
    {
        $ins = $order->getInvoices();
        foreach ($ins as $i) {
            $order->removeInvoice($i);
            $i->setUser(null);
            $i->setCustomer(null);
            $i->setInvoiceOrder(null);
            $this->em->remove($i);
        }
        $this->em->flush();
    }
}
