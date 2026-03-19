<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserOwnType;
use App\Form\UserPassType;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: ['de' => 'admin/mitarbeiter', 'en' => '/admin/employees'])]
class UserController extends AbstractController
{
    private readonly UserPasswordHasherInterface $passwordHasher;

    private readonly EntityManagerInterface $em;

    private FilesystemAdapter $fa;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
        $this->em = $em;
        $this->fa = new FilesystemAdapter();
    }

    #[Route(path: '/', name: 'backend_user_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('backend/user/index.html.twig', [
            'users' => $userRepository->findRealAll(),
        ]);
    }

    #[Route(path: ['de' => '/neu', 'en' => '/new'], name: 'backend_user_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, FileUploader $fileUploader): Response
    {
        $user = new User();
        $form = $this->createForm(UserOwnType::class, $user);
        $form->handleRequest($request);
        $post = $request->request->all();
        $post = $post['user_own'] ?? [];

        if ($form->isSubmitted()) {
            $userImage = null;

            if ($form->get('image')) {
                $userImage = $form->get('image')->getData();
            }
            if (!empty($post['plainPassword'])) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $post['plainPassword']['first']));
                $this->em->persist($user);
            }
            if (!empty($post['salutation'])) {
                $user->setSalutation($post['salutation']);
            }
            try {
                $this->em->persist($user);
                $this->em->flush();
                $this->em->refresh($user);

                if (!empty($userImage)) {
                    $name = $user->getUsername().'_'.$user->getId();
                    $userImageFileName = $fileUploader->upload($userImage, 'user', $name);
                    $user->setImage($userImageFileName);

                    $this->em->persist($user);
                    $this->em->flush();
                }
                $this->fa->delete('service_user');
            } catch (\Exception $e) {
//                $fe = new FormError('Der Nutzer existiert bereits');
//                $form->addError($fe);

                return $this->render('backend/user/new.html.twig', [
                    'user' => $user,
                    'form' => $form->createView(),
                ]);
            }

            return $this->redirectToRoute('backend_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('backend/user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: ['de' => '/{id}/bearbeiten', 'en' => '/{id}/edit'], name: 'backend_user_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MONTAGE')]
    public function edit(Request $request, FileUploader $fileUploader, User $user): Response
    {
        $post = $request->request->all();
        $post = $post['user'] ?? $post['user_pass'] ?? $post['user_new'] ?? $post['user_edit'] ?? $post['user_own'] ?? [];
        $formPass = null;
        if (!$this->isGranted('ROLE_ADMIN')) {
            if ($user->getUserIdentifier() !== $this->getUser()->getUserIdentifier()) {
                $this->addFlash('error', 'Keine Berechtigung!');
                $this->redirectToRoute('sf_montage');
            }
            $form = $this->createForm(UserOwnType::class, $user);
            $form->handleRequest($request);
        } elseif ($user->getUserIdentifier() === $this->getUser()->getUserIdentifier()) {
            $form = $this->createForm(UserOwnType::class, $user);
            $form->handleRequest($request);
        } else {
            $formPass = $this->createForm(UserPassType::class, $user);
            $formPass->handleRequest($request);
            if ($formPass->isSubmitted() && $formPass->isValid()) {
                $us = $formPass->getData();
                if (!empty($post['plainPassword'])) {
                    $user->setPassword($this->passwordHasher->hashPassword($us, $post['plainPassword']['first']));
                    $this->em->persist($us);
                }
                $this->em->flush();
                $this->addFlash('success', 'Das Passwort für '.$user->getUsername().' wurde zu '.$user->getPlainPassword().' geändert!');
                $this->fa->delete('service_user');

                return $this->redirectToRoute('backend_user_edit', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
            }

            $form = $this->createForm(UserType::class, $user);
            $form->handleRequest($request);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $us = $form->getData();
            $userImage = !empty($form->get('image')) ? $form->get('image')->getData() : null;
            if (!empty($userImage)) {
                $name = $user->getUsername().'_'.$user->getId();
                $userImageFileName = $fileUploader->upload($userImage, 'user', $name);
                $user->setImage($userImageFileName);
            }
            if (!empty($us->getPlainPassword())) {
                $password = $this->passwordHasher->hashPassword($us, $us->getPlainPassword());
                $us->setPassword($password);
                $this->em->persist($us);
                $this->addFlash('success', 'Der Mitarbeiter '.$us->getUsername().' wurde erfolgreich gespeichert!');
            }
            $this->em->persist($us);
            $this->em->flush();
            $this->fa->delete('service_user');
            if ($user->getId() === $this->getUser()->getId()) {
                $this->addFlash('success', 'Dein Profil wurde gespeichert');
            } else {
                $this->addFlash('success', 'Das Profil wurde gespeichert');
            }

            return $this->redirectToRoute('backend_user_edit', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
        } elseif ($form->isSubmitted()) {
            foreach ($form->getErrors() as $error) {
                $this->addFlash('danger', $error->getMessage());
            }

            return $this->redirectToRoute('backend_user_edit', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('backend/user/edit.html.twig', [
            'user' => $user,
            'users' => $this->em->getRepository(User::class)->findAll(),
            'form' => $form->createView(),
            'formPass' => $formPass instanceof FormInterface ? $formPass->createView() : null,
        ]);
    }

    #[Route(path: ['de' => '/{id}/loeschen', 'en' => '/{id}/delete'], name: 'backend_user_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $user->setInvoices(null);
            $user->setInquiries(null);
            $this->em->remove($user);
            $this->em->flush();
            $this->fa->delete('service_user');
        }

        return $this->redirectToRoute('backend_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
