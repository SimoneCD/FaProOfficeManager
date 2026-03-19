<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\OfferQuestion;
use App\Entity\QuestionArea;
use App\Repository\QuestionAreaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class GrokController extends AbstractController
{
    use TargetPathTrait;

    public function __construct(private EntityManagerInterface $em, private MessageBusInterface $bus)
    {
    }

    #[Route('/grok/dependencies', name: 'dependencies')]
    public function listDependencies(QuestionAreaRepository $dependencyRepository): JsonResponse
    {
        $dependencies = $dependencyRepository->getDependencies(23);
        $dependenciesArray = [];

        /** @var QuestionArea $dependency */
        foreach ($dependencies as $dependency) {
            $i = 0;
            $dependencyEntry = [
                'id' => $dependency->getId(),
                'title' => $dependency->getName(),
                'questions' => [
                    $i => [],
                ],
            ];
            /* @var OfferQuestion $area */
            foreach ($dependency->getQuestions() as $question) {
                $ii = 0;
                $dependencyEntry['questions'][$i] = [
                    'id' => $question->getId(),
                    'name' => $question->getName(),
                    'sort' => $question->getSort(),
                    'answers' => [
                        $ii => [],
                    ],
                ];
                foreach ($question->getOfferAnswers() as $answers) {
                    $dependencyEntry['questions'][$i]['answers'][$ii] = [
                        'name' => $answers->getName(),
                        'help' => $answers->getHelptext(),
                        'id' => $answers->getId(),
                    ];
                    ++$ii;
                }
                ++$i;
            }

            $dependenciesArray[] = $dependencyEntry;
        }

        return new JsonResponse($dependenciesArray);
    }

    #[Route(path: '/grok', name: 'app_grok', methods: ['GET', 'POST'])]
    public function grok(Request $request): Response
    {
        $post = $request->request->get('grok');
        // dd($post);
        if (null !== $post) {
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
                        'content' => $post,
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

            // Hier kannst du auf die Elemente des Arrays zugreifen, z.B.:
            // echo $response['id']; // Gibt die ID aus
            // echo $response['choices'][0]['message']['content']; // Gibt den Inhalt der Nachricht aus
            return $this->render('dashboard/index.html.twig', [
                'question' => $post,
                'grok' => $grok,
            ]);
        }

        return $this->render('grok/index.html.twig', [
            'grok' => null,
        ]);
    }
}
