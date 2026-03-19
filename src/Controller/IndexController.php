<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ActionLog;
use App\Entity\User;
use App\Service\SlackService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Class IndexController.
 */
class IndexController extends BaseController
{
    #[Route(path: '/', name: 'dashboard_index', methods: ['GET'])]
    public function dashboard(Request $request, SlackService $slackService): Response
    {
        if (!$this->getUser() instanceof User) {
            return $this->redirectToRoute('security_logout');
        }
        //        if ($this->getParameter('app_active_log')['slack_active']) {
        //            $userlist = $this->fa->get('slack-userlist', function () use ($slackService) {
        //                return $slackService->getUserList();
        //            });
        //            if (!count($userlist)) {
        //                $userlist = $slackService->getUserList();
        //            }
        //            $channellist = $this->fa->get('slack-channel', function () use ($slackService) {
        //                return $slackService->getChannelList();
        //            });
        //            $emojilist = $this->fa->get('slack-emoji2', function () use ($slackService) {
        //                return $slackService->getEmojiList();
        //            });
        //        } else {
        //            $userlist = [];
        //            $channellist = [];
        //            $emojilist = [];
        //        }
        if (!$this->isGranted('ROLE_MONTAGE')) {
            // dd($userOrders);
            return $this->render('dashboard/index.html.twig', [
                //                'emojis' => $emojilist,
                //                'userlist' => $userlist,
                //                'channellist' => $channellist,
                'user' => $this->getUser(),
                'notes' => [],
                'category' => null,
                'tuieditor' => 0,
            ]);
        }
        $postG = $request->request->get('grok');
        $grok = null;
        // dd($post);
        if (null !== $postG) {
            // URL und Daten für die API-Anfrage
            $url = 'https://api.x.ai/v1/chat/completions';
            $apiKey = $_ENV['XAI_API_KEY'];

            // JSON-Daten, die an die API gesendet werden
            $data = [
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a Salateur assistant. Speeking german',
                    ],
                    [
                        'role' => 'user',
                        'content' => $postG,
                    ],
                ],
                'model' => 'grok-beta',
                'stream' => false,
                'temperature' => 0,
            ];

            // Initialisieren des cURL Handles
            $ch = curl_init($url);

            // Setzen der Optionen für das cURL Handle
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer '.$apiKey,
            ]);

            // API Anfrage ausführen
            $jsonString = curl_exec($ch);
            // Fehlermeldung ausgeben, falls ein Fehler aufgetreten ist
            if (false === $jsonString) {
                $grok = 'Curl-Error: '.curl_error($ch);
            } else {
                $response = json_decode($jsonString, true);
                $grok = $response['choices'][0]['message']['content'];
                // $pattern = '/\*\*([^\*]+)\*\*/';
                // Ersetze die Sternchen durch <b> und </b> Tags
                // $content = preg_replace($pattern, '<b>$1</b>', $response['choices'][0]['message']['content']);
                // $response = str_replace('###', '<br><br>', $content);
            }

            // cURL Handle schließen
            curl_close($ch);
        }
        $cryptoActive = $this->getParameter('app_active_log')['app_crypto'];


        return $this->render('dashboard/index.html.twig', [

            'question' => $postG,
            'cryptoActive' => $cryptoActive,
            'grok' => $grok,
            'users' => $this->getServiceUsers(),
            //            'userOrders' => $userOrders,
            //            'emojis' => $emojilist,
            //            'userlist' => $userlist,
            //            'channellist' => $channellist,
            'teams' => $this->getTeams(),
            'user' => $this->getUser(),
            // 'inquiries' => $offers,
            'category' => null,
            'tuieditor' => 1,
            'ActionLog' => ActionLog::TYPE_CHOICES,
            'notes' => $this->em->getRepository(ActionLog::class)->findLastWeekNotes(),
        ]);
    }

    #[Route('/slack', name: 'app_ajax_slacking')]
    #[IsGranted('ROLE_MONTAGE')]
    public function slack(Request $request, SlackService $slackService): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $users = explode(',', $request->request->get('user'));
        if (!empty($this->parameter['app_active_log']['slack_active'])) {
            return $this->json('Slack ist nicht aktiviert!');
        }
        $mkdown = str_replace(['**', '~~', '\_'], ['*', '~', '_'], $request->request->get('mkdwn'));

        $channelSlackId = $request->request->get('channel');
        if (!empty($users[0]) && 1 == (int) $request->request->get('u') && str_starts_with($users[0], 'U')) {
            /** @var User $admin */
            $admin = $this->getUser();
            if ($admin instanceof User && !empty($admin->getSlackId())) {
                $now = $request->request->get('now');
                foreach ($users as $k => $userSlackId) {
                    if (!empty($now)) {
                        $r = $slackService->faProSlackToUser($userSlackId, 'Mitteilung von <@'.$admin->getSlackId().">:\n".$mkdown);
                    } else {
                        $us = $this->em->getRepository(User::class)->findOneBy([
                            'slackId' => $userSlackId,
                        ]);
                        $time = explode(' ', $request->request->get('time'));
                        $d = explode('.', $time[0]);
                        $newTime = new \DateTime($d[2].'-'.$d[1].'-'.$d[0].' '.$time[1]);
                        $text = 'Mitteilung von <@'.$this->getUser()->getSlackId().'>';
                        $r = $slackService->faProSlackReminderToUser($us, $text, $mkdown, $newTime);
                    }
                }

                return $this->json($r);
            }

            return $this->json('Bitte überprüfe Deine SlackId!');
        } elseif (!empty($channelSlackId) && str_starts_with($channelSlackId, 'C')) {
            if ($this->getUser() instanceof User) {
                if (!empty($request->request->get('now'))) {
                    $r = $slackService->faProSlackToChannel($this->getUser(), $channelSlackId, 'Mitteilung von <@'.$this->getUser()->getSlackId().">:\n".$mkdown);
                } else {
                    $time = explode(' ', $request->request->get('time'));
                    $d = explode('.', $time[0]);
                    $newTime = new \DateTime($d[2].'-'.$d[1].'-'.$d[0].' '.$time[1]);
                    $text = 'Mitteilung von <@'.$this->getUser()->getSlackId().'>:';
                    $r = $slackService->faProSlackReminderToChannel($channelSlackId, $text, $mkdown, $newTime);
                }

                return $this->json($r);
            }

            return $this->json('Kein Channel mit folgender SlackId('.$channelSlackId.')');
        }

        return $this->json(false);
    }
}
