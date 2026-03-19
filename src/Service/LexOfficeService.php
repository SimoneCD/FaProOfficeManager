<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Http\Client\ClientInterface;
use Sysix\LexOffice\Api;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class LexOfficeService
{
    private readonly Api $api;

    public function __construct(ClientInterface $client)
    {
        $this->api = new Api($_ENV['APP_LEX_KEY'], $client);
//        $cacheInterface = new FilesystemAdapter(
//            'lexoffice',
//            3600,
//            __DIR__.'/../../var/cache'
//        );
        #$this->api->s($cacheInterface);
    }

    public function getAllInvoices()
    {
        return $this->api->downPaymentInvoice()->getAll()->getBody()->getContents();
        // return $this->api->downPaymentInvoice()->getAll()->getBody()->getContents();
    }

    public function getContact()
    {
        // dd($this->api->contact()->get('97adba79-1a80-4c2f-8db9-f7d7261d1b2e'));
        return $this->api->contact()->getPage(0);
        // return $this->api->downPaymentInvoice()->getAll()->getBody()->getContents();
    }

    public function getVoucherList()
    {
        $client = $this->api->voucherlist();
//
//        $client->size = 100;
//        $client->sortDirection = 'DESC';
//        $client->sortColumn = 'voucherNumber';
//        $client->types = [
//            'salesinvoice',
//            'salescreditnote',
//            'purchaseinvoice',
//            'purchasecreditnote',
//            'invoice',
//            'downpaymentinvoice',
//            'creditnote',
//            'orderconfirmation',
//            'quotation',
//        ];
//        $client->statuses = [
//            'draft',
//            'open',
//            'paid',
//            'paidoff',
//            'voided',
//            // 'overdue', overdue can only be fetched alone
//            'accepted',
//            'rejected',
//        ];
//
//        // get everything what we can, not recommend:
//        // $client->setToEverything()
//
//        // get a page
//        return $client->getPage(0);
//        $client = $api->voucherlist();

        $client->size = 100;
        $client->sortDirection = 'DESC';
        $client->sortColumn = 'voucherNumber';

// filters required
        $client->types = [
            'salesinvoice',
            'salescreditnote',
            'purchaseinvoice',
            'purchasecreditnote',
            'invoice',
            'downpaymentinvoice',
            'creditnote',
            'orderconfirmation',
            'quotation'
        ];
        $client->statuses = [
            'draft',
            'open',
            'paid',
            'paidoff',
            'voided',
            //'overdue', overdue can only be fetched alone
            'accepted',
            'rejected'
        ];

// filters optional
        $client->archived = true;
        $client->contactId = uniqid('um-');
        $client->voucherDateFrom = new \DateTime('2023-12-01');
        $client->voucherDateTo = new \DateTime();
        $client->createdDateFrom = new \DateTime('2023-12-01');;
        $client->createdDateTo = new \DateTime();
        $client->updatedDateFrom = new \DateTime('2023-12-01');
        $client->updatedDateTo = new \DateTime();

// get a page
        $response = $client->getPage(0);

        return $response;
    }
}
