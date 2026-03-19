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

#[Route(path: ['de' => '/links', 'en' => '/linked'])]
class LinkController extends BaseController
{
    use TargetPathTrait;

    public function __construct(EntityManagerInterface $em, HttpClientInterface $client, PHPMailerService $mailerService, TranslatorInterface $translator, string $subdomain, private string $docDirectory)
    {
        parent::__construct($em, $client, $mailerService, $translator, $subdomain);
    }

    #[Route('/', name: 'app_link_index', methods: ['GET', 'POST'])]
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

    #[Route('/submit', name: 'ajax_link_submit', methods: ['GET', 'POST'])]
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

    #[Route('/new', name: 'app_link_new', methods: ['GET', 'POST'])]
    public function new(Request $request, LinkRepository $linkRepository): Response
    {
        $link = new Link();
        $form = $this->createForm(LinkType::class, $link);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $linkRepository->save($link, true);

            return $this->redirectToRoute('app_link_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('backend/link/new.html.twig', [
            'link' => $link,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_link_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Link $link, LinkRepository $linkRepository): Response
    {
        $form = $this->createForm(LinkType::class, $link);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $linkRepository->save($link, true);

            return $this->redirectToRoute('app_link_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('backend/link/edit.html.twig', [
            'link' => $link,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_link_delete', methods: ['POST'])]
    public function delete(Request $request, Link $link, LinkRepository $linkRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$link->getId(), $request->request->get('_token'))) {
            $linkRepository->remove($link, true);
        }

        return $this->redirectToRoute('app_link_index', [], Response::HTTP_SEE_OTHER);
    }
}
