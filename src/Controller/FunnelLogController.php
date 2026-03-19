<?php

namespace App\Controller;

use _PHPStan_95cdbe577\Nette\Utils\DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: 'log')]
class FunnelLogController extends AbstractController
{
    #[Route('/', name: 'app_funnel_log', methods: ['POST'])]
    public function index(Request $request): Response
    {
        error_reporting(E_ALL);
        $data = $request->request->get('question');
        $date = new DateTime();
        $f = fopen('file-23hzns6dzj3.txt', 'a+');
        fwrite($f, $date->format('d.m.Y H-i').' - '.$data.' - '.$_SERVER['REMOTE_ADDR'].' - '.$_SERVER['HTTP_USER_AGENT']."\n");
        fclose($f);

        return $this->json(true);
    }
}
