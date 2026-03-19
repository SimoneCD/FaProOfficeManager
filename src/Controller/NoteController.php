<?php

namespace App\Controller;

use App\Entity\ActionLog;
use App\Entity\Customer;
use App\Entity\Offer;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MONTAGE')]
class NoteController extends BaseController
{
    #[Route('/notes/create/offer/{offer}/', name: 'note_create_offer', methods: ['POST'])]
    public function createNote(Request $request, Offer $offer, Security $security)
    {
        $content = $request->request->get('noteContent');
        $type = $request->request->get('type') ?? 'info'; // Neuer Parameter

        /** @var User $user */
        $user = $security->getUser();
        $tr = array_map(function ($choice) {
            return $choice['label'];
        }, ActionLog::TYPE_CHOICES);
        $tr = array_combine($tr, array_keys(ActionLog::TYPE_CHOICES));
        // $tr = array_flip(ActionLogType::TYPE_CHOICES);
        // $tr = self::TYPE_CHOICES;
        $flippedTr = array_flip($tr);
        $customer = $offer->getCustomer();
        $this->log2(
            $type,
            $flippedTr[$type].' - '.$user->getFullName(),
            $content,
            $customer,
            $offer,
        );
        $date = new \DateTime('2023-10-01 10:00:00'); // Beispiel-Datum

        // Aktuelles Datum und Uhrzeit
        $now = new \DateTime();

        // Differenz berechnen
        $interval = $now->diff($date);
        // Zeitdifferenz als String
        $ago = $this->getAgoString($interval);

        return $this->json([
            'type' => $type,
            'title' => $flippedTr[$type].' - '.$user->getFullName(),
            'content' => $content,
            'avatar' => $user->getImage(),
            'username' => $user->getUsername(),
            'date' => $date->format('d.m.y H:i'),
            'ago' => $ago,
            'success' => true,
        ]);
    }

    #[Route('/notes/create/answer/{note}/', name: 'note_answer_offer', methods: ['POST'])]
    public function createAnswer(Request $request, ActionLog $note, Security $security)
    {
        $post = $request->request->all();
        $content = $post['noteContent'];
        $type = $post['type'] ?: 'info';

        /** @var User $user */
        $user = $security->getUser();
        $tr = array_map(function ($choice) {
            return $choice['label'];
        }, ActionLog::TYPE_CHOICES);
        $tr = array_combine($tr, array_keys(ActionLog::TYPE_CHOICES));
        // $tr = array_flip(ActionLogType::TYPE_CHOICES);
        // $tr = self::TYPE_CHOICES;
        $flippedTr = array_flip($tr);
        $this->logAnswer(
            $type,
            $flippedTr[$type].' - '.$user->getFullName(),
            $content,
            $note->getCustomer(),
            $note->getOffer(),
            $note,
        );
        $date = new \DateTime('2023-10-01 10:00:00'); // Beispiel-Datum

        // Aktuelles Datum und Uhrzeit
        $now = new \DateTime();

        // Differenz berechnen
        $interval = $now->diff($date);
        // Zeitdifferenz als String
        $ago = $this->getAgoString($interval);

        return $this->json([
            'type' => $type,
            'title' => $flippedTr[$type].' - '.$user->getFullName(),
            'content' => $content,
            'avatar' => $user->getImage(),
            'username' => $user->getUsername(),
            'date' => $date->format('d.m.y H:i'),
            'ago' => $ago,
            'success' => true,
        ]);
    }

    #[Route('/notes/delete/note/{note}/', name: 'note_delete', methods: ['POST'])]
    #[IsGranted('ROLE_MONTAGE')]
    public function deleteAnswer(ActionLog $note)
    {
        $answers = $note->getAnswers();
        if (!empty($answers)) {
            foreach ($answers as $answer) {
                $this->em->getRepository(ActionLog::class)->remove($answer);
            }
        }
        $this->em->getRepository(ActionLog::class)->remove($note, true);

        return $this->json(true);
    }

    private function getAgoString($interval)
    {
        if ($interval->y > 0) {
            return $interval->format('%y '.(1 == $interval->y ? 'Jahr' : 'Jahren'));
        }
        if ($interval->m > 0) {
            return $interval->format('%m '.(1 == $interval->m ? 'Monat' : 'Monaten'));
        }
        if ($interval->d > 0) {
            return $interval->format('%d '.(1 == $interval->d ? 'Tag' : 'Tagen'));
        }
        if ($interval->h > 0) {
            return $interval->format('%h '.(1 == $interval->h ? 'Stunde' : 'Stunden'));
        }
        if ($interval->i > 0) {
            return $interval->format('%i '.(1 == $interval->i ? 'Minute' : 'Minuten'));
        }
        if ($interval->s > 0) {
            return $interval->format('%s '.(1 == $interval->s ? 'Sekunde' : 'Sekunden'));
        }

        return 'gerade eben';
    }

    // Hier wäre die Log-Methode, die den Typen verarbeiten kann
    private function log2($type, $description, $message, $customer, $offer)
    {
        $this->log(
            $type,
            $description,
            $message,
            $customer,
            $offer,
        );
        // Logik zum Protokollieren der Notiz
        // Hier könntest du $noteType verwenden, um die Art der Notiz zu unterscheiden
        // Zum Beispiel, könnte `ActionLog` eine Eigenschaft haben, um den Notiztyp zu speichern
    }

    // Hier wäre die Log-Methode, die den Typen verarbeiten kann
    private function logAnswer($type, $description, $message, $customer, $offer, $answer)
    {
        $this->log(
            $type,
            $description,
            $message,
            $customer,
            $offer,
            $answer
        );
        // Logik zum Protokollieren der Notiz
        // Hier könntest du $noteType verwenden, um die Art der Notiz zu unterscheiden
        // Zum Beispiel, könnte `ActionLog` eine Eigenschaft haben, um den Notiztyp zu speichern
    }

    #[Route('/notes/create/customer/{customer}/', name: 'note_create_customer', methods: ['POST'])]
    public function createCustomerNote(Request $request, Customer $customer, EntityManagerInterface $entityManager)
    {
        $content = $request->request->get('noteContent');
        /** @var User $user */
        $user = $this->getUser();
        $this->log(
            'info',
            'Neue Notiz von '.$user->getFullName().'',
            $content,
            $customer,
        );

        return $this->json(['success' => true]);
    }
}
