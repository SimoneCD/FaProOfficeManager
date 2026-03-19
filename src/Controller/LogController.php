<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Link;
use App\Form\LinkType;
use App\Repository\LinkRepository;
use App\Service\PHPMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: ['de' => '/log', 'en' => '/logs'])]
class LogController extends BaseController
{
    use TargetPathTrait;

    public function __construct(EntityManagerInterface $em, HttpClientInterface $client, PHPMailerService $mailerService, TranslatorInterface $translator, string $subdomain, private string $docDirectory)
    {
        parent::__construct($em, $client, $mailerService, $translator, $subdomain);
    }

    #[Route('/write', name: 'app_log_write', methods: ['GET', 'POST'])]
    public function index(Request $request, LinkRepository $linkRepository): Response
    {
        $link = new Link();
        $form = $this->createForm(LinkType::class, $link);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $linkRepository->save($link, true);

            return $this->redirectToRoute('app_link_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('backend/link/index.html.twig', [
            'links' => $linkRepository->findAll(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/read', name: 'app_log_read', methods: ['GET', 'POST'])]
    public function saveLink(Request $request, EntityManagerInterface $entityManager): Response
    {
        $link = new Link();
        $form = $this->createForm(LinkType::class, $link);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $linkOld = $entityManager->getRepository(Link::class)->findOneBy([
                'email' => $link->getEmail(),
            ]);
            if ($linkOld instanceof Link) {
                return $this->json(false);
            }
            $data = $form->getData();
            $data->setLink(strtoupper($this->getRandomString(8)));
            try {
                $entityManager->persist($data);
                $entityManager->flush();
            } catch (\Exception $exception) {
                // dd($exception);
                return $this->json(false);
            }

            return $this->json(true);
        }

        return $this->json(false);
    }
}
