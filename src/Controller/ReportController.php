<?php

declare(strict_types=1);

namespace App\Controller;
use App\Service\PHPMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: ['de' => '/bericht', 'en' => '/report'])]
#[IsGranted('ROLE_MONTAGE')]
class ReportController extends BaseController
{
    use TargetPathTrait;

    public function __construct(EntityManagerInterface $em, HttpClientInterface $client, PHPMailerService $mailerService, TranslatorInterface $translator, string $subdomain, private string $docDirectory)
    {
        parent::__construct($em, $client, $mailerService, $translator, $subdomain);
    }

    #[Route(path: '/', name: 'app_report', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MONTAGE')]
    public function index(string $protocolDirectory): Response
    {
        return $this->json('todo');

    }
}
