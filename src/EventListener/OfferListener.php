<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Service\SlackService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * todo change.
 */
class OfferListener
{
    private const REMINDER_MIN = 320; // 5 minutes
    private const REMINDER_HOUR = 3600; // 1 hour
    private const REMINDER_DAY = 86400; // 1 day .. in seconds

    public EntityManagerInterface $em;

    public $chatter;

    public $storage;

    public $slackService;

    public $parameter;

    public function __construct(EntityManager $entityManager, ChatterInterface $chatter, TokenStorageInterface $storage, SlackService $slackService, ParameterBagInterface $parameter)
    {
        $this->em = $entityManager;
        $this->chatter = $chatter;
        $this->storage = $storage;
        $this->slackService = $slackService;
        $this->parameter = $parameter->all();
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            // don't do anything if it's not the main request
            return;
        }
        $gina = 16;
        $jerry = 2;
        $simone = 17;
        $token = $this->storage->getToken();
        $date = new \DateTime();
        $request = $event->getRequest();
        if ($token instanceof TokenInterface) {
            if ($_ENV['APP_ENV'] === 'dev') {
                $user = null; // $this->em->getRepository(User::class)->find($simone);
                // nur zum testen todo create tests
                //                $user = $this->em->getRepository(User::class)->find($simone);
                //                $this->slackService->addSlackReminderForUser(
                //                    $this->em->getRepository(User::class)->find($simone),
                //                    '*Anruftermin (14Uhr)*'."\n".'Herr XXXXXX'."\n".'ABC Str. 3'."\n".'12345 Berlin'."\n".' <https://kundenservice.zukunftsorientierte-energie.de/>',
                //                    $date->modify('+10 seconds')
                //                );
            } else {
                $user = $token->getUser();
            }
            if ($user instanceof User && !stristr($request->getRequestUri(), 'ajax')) {
                $session = $request->getSession();
                // $seconds = time() - $session->getMetadataBag()->getLastUsed();
                try {
                    if ($user->isSlackLog()) {
                        try {
                            $this->slackService->addSlackLogToChannel('slack_log', $user->getUsername().' '.$date->format('H:i').' aktive in '.$request->getRequestUri());
                        } catch (\Exception $exception) {
                        }
                    // } elseif ($seconds >= 600) {
//                        if (($seconds / 60) > 0) {
//                            $hours = ($seconds / 60) % 60;
//                            $tr = round($hours / 60, 0).' Stunden und '.round($seconds - ($hours * 60), 0).' Minuten';
//                        } else {
//                            $tr = round($seconds / 60, 0).' Minuten';
//                        }
                        // return;
                        // $this->slackService->addSlackLogToChannel('slack_log', $user->getUsername().' war abwesend: '.$tr);
                    }
                } catch (\Exception $exception) {
                    return;
                }
            }
        }

        //        $this->em
        //            ->getFilters()
        //            ->enable('deleted_offers_out');
        // $filter->setParameter('status', 'deleted');
    }
}
