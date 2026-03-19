<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Timesheet;
use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(['de' => '/zeiterfassung', 'en' => '/timesheet'])]
#[IsGranted('ROLE_MONTAGE')]
class TimesheetController extends BaseController
{
    #[Route(path: '/', name: 'ajax_timesheet_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $post = json_decode($request->getContent());
        if ($this->isGranted('ROLE_ADMIN')) {
            $userId = isset($post->user) ? $post->user : $this->getUser()->getId();
            $user = $this->em->getRepository(User::class)->find($userId);
        } else {
            $user = $this->em->getRepository(User::class)->find($this->getUser()->getId());
        }

        return $this->render('timesheet/index.html.twig', [
            // 'users' => $this->getServiceUsers(),
            'user' => $user,
        ]);

    }
    #[Route(path: '/list', name: 'ajax_timesheet_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $post = json_decode($request->getContent());
        if ($this->isGranted('ROLE_ADMIN')) {
            $userId = isset($post->user) ? $post->user : $this->getUser()->getId();
            $user = $this->em->getRepository(User::class)->find($userId);
            $sheets = $this->em->getRepository(Timesheet::class)->findBy(['user' => $user]);
        } else {
            $user = $this->em->getRepository(User::class)->find($this->getUser()->getId());
            /** @var Collection $sheets */
            $sheets = $this->em->getRepository(Timesheet::class)->findBy(['user' => $user]);
        }
        $s = [];
        foreach ($sheets as $sheet) {
            $s[] = $sheet->toArray();
        }

        return $this->json($s);
    }

    #[Route(path: '/create', name: 'ajax_timesheet_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $post = json_decode($request->getContent());
        if (!empty($post)) {
            $timesheet = new Timesheet();
            $timesheet->setName('-');
            $timesheet->setTitle($post->text);
            $timesheet->setUser($this->getUser());
            $timesheet->setCreatedAt(new \DateTimeImmutable());
            $timesheet->setStart(new \DateTime($post->start));
            $timesheet->setEnd(new \DateTime($post->end));

            $this->em->persist($timesheet);
            $this->em->flush();
            $this->em->refresh($timesheet);

            return $this->json($timesheet->toArray());
        }

        return $this->json(false);
    }

    #[Route(path: '/update', name: 'ajax_timesheet_update', methods: ['GET', 'POST'])]
    public function update(Request $request): Response
    {
        $post = json_decode($request->getContent());
        $date = new \DateTime();
        if ($this->isGranted('ROLE_MONTAGE')) {
            $id = $post->id;
            $sheet = $this->em->getRepository(Timesheet::class)->find($id);
            $sheet->setStart(new \DateTime($post->start));
            $sheet->setEnd(new \DateTime($post->end));
            $sheet->setTitle($post->text);
            $sheet->setName('updated - '.$date->format('d.m H:i'));
            $this->em->persist($sheet);
            $this->em->flush();
            $this->em->refresh($sheet);

            return $this->json($sheet->toArray());
        }

        return $this->json(false);
    }

    #[Route(path: '/del', name: 'ajax_timesheet_delete', methods: ['DELETE', 'POST'])]
    public function delete(Request $request): Response
    {
        $post = json_decode($request->getContent());
        $sheet = $this->em->getRepository(Timesheet::class)->find($post->id);

        $this->em->remove($sheet);
        $this->em->flush();

        return $this->json(true);
    }
}
