<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 *
 */
#[Route(path: '/backend/crawl')]
#[IsGranted('ROLE_EMPLOYEE_SERVICE')]
class Crawl2Controller extends AbstractController
{
    final public const SPACE = '%20';
    // const SUCH_URL = 'https://www.dasoertliche.de/?zvo_ok=4&buc=&plz=1....&quarter=&district=%s&ciid=3336&kw=%s&ci=Berlin+Bezirk+%s&kgs=11000000000&buab=&zbuab=&form_name=search_nat';
    final public const SUCH_URL = 'https://www.dasoertliche.de/?zvo_ok=4&plz=1....&quarter=&district=&ciid=&kw=Kaufhaus&ci=Berlin&kgs=11000000000&buab=&zbuab=&form_name=search_nat';
    final public const TOWNS = [
        'Aachen',
        'Augsburg',
        'Bergisch Gladbach',
        'Bielefeld',
        'Berlin',
        // 'Bochum',
        'Bonn',
        'Bottrop',
        'Braunschweig',
        'Bremen',
        'Bremerhaven',
        'Chemnitz',
        'Darmstadt',
        'Dortmund',
        'Dresden',
        'Duisburg',
        'Düsseldorf',
        'Erfurt',
        'Erlangen',
        'Essen',
        'Frankfurt am Main',
        'Freiburg im Breisgau',
        'Fürth',
        'Gelsenkirchen',
        'Göttingen',
        'Gütersloh',
        'Hagen',
        'Halle (Saale)',
        'Hamburg',
        'Hamm',
        'Hannover',
        'Heidelberg',
        'Heilbronn',
        'Herne',
        'Hildesheim',
        'Ingolstadt',
        'Jena',
        'Karlsruhe',
        'Kassel',
        'Kiel',
        'Koblenz',
        'Köln',
        'Krefeld',
        'Leipzig',
        'Leverkusen',
        'Lübeck',
        'Ludwigshafen am Rhein',
        'Magdeburg',
        'Mainz',
        'Mannheim',
        'Moers',
        'Mönchen-gladbach',
        'Mühlheim an der Ruhr',
        'München',
        'Münster',
        'Neuss',
        'Nürnberg',
        'Oberhausen',
        'Offenbach am Main',
        'Oldenburg',
        'Osnabrück',
        'Paderborn',
        'Pforzheim',
        'Potsdam',
        'Recklinghausen',
        'Regensburg',
        'Remscheid',
        'Reutlingen',
        'Rostock',
        'Saarbrücken',
        'Salzgitter',
        'Siegen',
        'Solingen',
        'Stuttgart',
        'Trier',
        'Ulm',
        'Wiesbaden',
        'Wolfsburg',
        'Wuppertal',
        'Würzburg',
    ];

    #[Route(path: '/download/{filename}', name: 'backend_crawl_oeffentliche_file', methods: ['GET'])]
    public function download(AssetExtension $e, string $filename): Response
    {
        $response = new Response(file_get_contents($filename));
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }

    /**
     * old.
     */
    #[Route(path: '/{crawl}/town/{town}', name: 'backend_crawl_town_oeffentliche', methods: ['GET'])]
    public function crawlTown(string $crawl, ?string $town = 'Berlin'): Response
    {
        if ($crawl === 'q') {
            return $this->render('backend/crawl/search.html.twig', ['file' => '', 'towns' => self::TOWNS]);
        }
        $browser = new HttpBrowser(HttpClient::create());
        $town = str_replace(' ', '%20', $town);
        switch ($town) {
            case 'Berlin':
                $zips = ['Berlin' => ['10115', '10117', '10119', '10178', '10179', '10243', '10245', '10247', '10249', '10315', '10317', '10318', '10319', '10365', '10367', '10369', '10405', '10407', '10409', '10435', '10437', '10439', '10551', '10553', '10555', '10557', '10559', '10585', '10587', '10589', '10623', '10625', '10627', '10629', '10707', '10709', '10711', '10713', '10715', '10717', '10719', '10777', '10779', '10781', '10783', '10785', '10787', '10789', '10823', '10825', '10827', '10829', '10961', '10963', '10965', '10967', '10969', '10997', '10999', '12043', '12045', '12047', '12049', '12051', '12053', '12055', '12057', '12059', '12099', '12101', '12103', '12105', '12107', '12109', '12157', '12159', '12161', '12163', '12165', '12167', '12169', '12203', '12205', '12207', '12209', '12247', '12249', '12277', '12279', '12305', '12307', '12309', '12347', '12349', '12351', '12353', '12355', '12357', '12359', '12435', '12437', '12439', '12459', '12487', '12489', '12524', '12526', '12527', '12555', '12557', '12559', '12587', '12589', '12619', '12621', '12623', '12627', '12629', '12679', '12681', '12683', '12685', '12687', '12689', '13051', '13053', '13055', '13057', '13059', '13086', '13088', '13089', '13125', '13127', '13129', '13156', '13158', '13159', '13187', '13189', '13347', '13349', '13351', '13353', '13355', '13357', '13359', '13403', '13405', '13407', '13409', '13435', '13437', '13439', '13465', '13467', '13469', '13503', '13505', '13507', '13509', '13581', '13583', '13585', '13587', '13589', '13591', '13593', '13595', '13597', '13599', '13627', '13629', '14050', '14052', '14053', '14055', '14057', '14059', '14089', '14109', '14129', '14163', '14165', '14167', '14169', '14193', '14195', '14197', '14199', '16321', '15378', '15378', '15345', '15366', '15562', '15566', '15754', '23823']];
                break;
            case 'Aachen':
                $zips = ['Aachen' => ['52062', '52064', '52066', '52068', '52070', '52072', '52074', '52076', '52078', '52080', '52072', '52076', '53804']];
                break;
            case 'Augsburg':
                $zips = ['Augsburg' => ['86150', '86152', '86153', '86154', '86156', '86157', '86159', '86161', '86163', '86165', '86167', '86169', '86179', '86199', '86343', '86477', '86477', '86199', '86495', '86500', '86500', '86500', '86465', '25813']];
                break;
            case 'Bergisch Gladbach':
                $zips = ['Bergisch Gladbach' => ['51427', '51429', '51465', '51467', '51469', '51427', '51429', '51429', '51467', '51469']];
                break;
            case 'Bielefeld':
                $zips = ['Bielefeld' => ['33602', '33604', '33605', '33607', '33609', '33611', '33613', '33615', '33617', '33619', '33647', '33719', '33739', '33605', '33647', '33647', '33649', '33649', '33659', '33659', '33659', '33689', '33689', '33699', '33699', '33719', '33729', '33729', '33739']];
                break;
            case 'Bonn':
                $zips = ['Bonn' => ['53111', '53113', '53115', '53117', '53119', '53121', '53123', '53125', '53127', '53129', '53173', '53175', '53177', '53179', '53225', '53227', '53229']];
                break;
            case 'Bottrop':
                $zips = ['Bottrop' => ['46236', '46238', '46240', '46242', '46244', '46244', '46244', '45966', '45966']];
                break;
            case 'Braunschweig':
                $zips = ['Braunschweig' => ['38100', '38102', '38104', '38106', '38108', '38110', '38112', '38114', '38116', '38118', '38120', '38122', '38124', '38126', '38108', '38110', '38112', '38122', '38122', '38124', '38126']];
                break;
            case 'Bremen':
                $zips = ['Bremen' => ['28195', '28197', '28199', '28201', '28203', '28205', '28207', '28209', '28211', '28213', '28215', '28217', '28219', '28237', '28239', '28259', '28277', '28279', '28307', '28309', '28325', '28327', '28329', '28355', '28357', '28359', '28717', '28719', '28755', '28757', '28759', '28777', '28779', '28832', '28832', '28832', '28844', '28844', '28844', '88279', '59469', '36419', '88367']];
                break;
            case 'Bremerhaven':
                $zips = ['Bremerhaven' => ['27568', '27570', '27572', '27574', '27576', '27578', '27580', '27616']];
                break;
            case 'Hamburg':
                $zips = ['Hamburg' => [false, '20095', '20097', '20099', '20144', '20146', '20148', '20149', '20249', '20251', '20253', '20255', '20257', '20259', '20354', '20355', '20357', '20359', '20457', '20459', '20535', '20537', '20539', '21029', '21031', '21033', '21035', '21037', '21039', '21073', '21075', '21077', '21079', '21107', '21109', '21129', '21147', '21149', '22041', '22043', '22045', '22047', '22049', '22081', '22083', '22085', '22087', '22089', '22111', '22113', '22115', '22117', '22119', '22143', '22145', '22147', '22149', '22159', '22175', '22177', '22179', '22297', '22299', '22301', '22303', '22305', '22307', '22309', '22335', '22337', '22339', '22359', '22391', '22393', '22395', '22397', '22399', '22415', '22417', '22419', '22453', '22455', '22457', '22459', '22523', '22525', '22527', '22529', '22547', '22549', '22559', '22587', '22589', '22605', '22607', '22609', '22761', '22763', '22765', '22767', '22769', '22869', '21521', '22145', '27499', '22145', '22889', '22889', '21465']];
                break;
            default:
                $html1 = $browser->request(
                    'GET',
                    'https://www.dasoertliche.de/Themen/Postleitzahlen/'.str_replace(' ', '%20', $town).'.html'
                );

                $crawler1 = new Crawler();
                $crawler1->addHtmlContent($html1->html());

                $zips = $crawler1->filterXPath('//tr')->each(function ($d) {
                    if ($d->filter('td')->count()) {
                        return $d->filter('td')->first()->text();
                    }

                    return false;
                });
        }

        $filename1 = str_replace(' ', '_', $crawl.'_'.$town);
        $filename1 .= '.csv';
        $filename = './search/'.$filename1;
        $fp = fopen($filename, 'w+');
        fputcsv($fp, ['Firmen-Name', 'Adresse', 'Telefon', 'E-Mail', 'Notitz', 'Bearbeitet']);
        foreach ($zips[$town] as $zip) {
            $q = str_replace(' ', '%20', $crawl);
            $p = [];
            // $url = 'https://www.dasoertliche.de/?zvo_ok=0&buc=&plz=&quarter=&district=&ciid=&kw='.$q.'&ci=&kgs=&buab=&zbuab=&form_name=search_nat&recFrom='.$cou++;
            $url = sprintf(
                'https://www.dasoertliche.de/?zvo_ok=0&plz=&quarter=&district=&ciid=&kw=%s&ci=%d&kgs=&buab=&zbuab=&form_name=search_nat',
                $q, $zip);
            $html = $browser->request('GET', $url);

            $crawler = new Crawler();
            $crawler->addHtmlContent($html->html());
            $treffer = $crawler->filterXPath('//div[contains(@class,"st-treffer")]');

            $p[] = $treffer->each(function ($e) use ($browser) {
                $phone = '';
                $countPhone = $e->filter('span.st-rufnr-nm')->count();
                if ($e->filter('span.st-treff-name')->count() !== 0) {
                    if ($countPhone > 1) {
                        $phone = $e->filter('span.st-rufnr-nm')->text();
                    } elseif ($countPhone !== 0) {
                        $phone = $e->filter('span.st-rufnr-nm')->text();
                    }
                    $name = $e->filter('span.st-treff-name')->text();
                    $address = $e->filter('address')->text();
                    $email = '';

                    if ($e->filter('a.mask-nr')->count() !== 0) {
                        $linkCrawler = $e->filter('a.mask-nr');
                        $link = $linkCrawler->link();
                        // dump($e->filter('a.mask-nr'));die;
                        $ul = $browser->click($link);
                        // dump($ul);die;
                        $html2 = $browser->request('GET', $ul->getUri());

                        $crawler3 = new Crawler();
                        $crawler3->addHtmlContent($html2->html());
                        $treffer = $crawler3->filterXPath('//table[contains(@class,"det_numbers")]');
                        // dump($crawler3->html());die;
                        $phone = $treffer->filter('table.det_numbers')->filter('span')->text();
                        $treffer2 = $crawler3->filterXPath('//div[contains(@class,"lnks")]');
                        $email = $treffer2->filter('a.mail span')->text();
                        // dump($email);die;
                    }

                    return [$name, $address, $phone, $email, '', ''];
                }

                return [];
            });
        }

        $has = [];
        foreach ($p as $a) {
            foreach ($a as $b) {
                if (!empty($b) && is_array($b) && count($b) && !in_array($b[2], $has)) {
                    fputcsv($fp, [$b[0], $b[1], str_replace([' ', 'Tel.'], '', (string) $b[2]), $b[3], '', '']);
                    $has[] = $b[2];
                }
            }
        }
        fclose($fp);

        $response = new Response(file_get_contents($filename));
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename1.'"');
        unset($filename);

        return $response;
    }

    /**
     * new.
     */
    #[Route(path: '/{crawl}/town/{town}/{page}', name: 'backend_crawl_one', methods: ['GET'])]
    public function crawl188(string $crawl, string $town = 'Berlin', int $page = 1): Response
    {
        $ta = ($page > 1 ? $page * 10 - 10 : $page - 1);
        $pages = 10 + ($ta * 10);

        if ($crawl === 'q') {
            return $this->render('backend/crawl/search.html.twig', ['file' => '', 'towns' => self::TOWNS]);
        }
        $browser = new HttpBrowser(HttpClient::create());
        $town = str_replace(' ', '%20', $town);

        $filename1 = str_replace(' ', '_', $crawl.'_'.$town.'-'.$ta.'_'.$pages);
        $filename1 .= '.csv';
        $filename = './search/'.$filename1;
        $fp = fopen($filename, 'w+');
        fputcsv($fp, ['Firmen-Name', 'Adresse', 'Telefon', 'E-Mail', 'Notitz', 'Bearbeitet']);

        $q = str_replace(' ', '%20', $crawl);
        $p = [];
        while ($ta < $pages) {
            sleep(1);
            $html = '';
            if (count($p) <= 5) {
                $url = sprintf('https://www.11880.com/suche/%s/%s?page=%d&query=', $q, $town, $ta + 1);
                // $url = sprintf('https://www.11880.com/suche?what=%s&where=%s&street=&lastname=&firstname=&firmen=1&personen=&page=%d&query=', strtolower($q), strtolower($town), ($ta+1));
                // dump($url);die;
                $html = $browser->request('GET', $url);
                $crawler = new Crawler();
                $crawler->addHtmlContent($html->html());
            } else {
                try {
                    /** @var Crawler $crawler */
                    /** @var Form $form */
                    $form = $crawler->filter('.next > .link-form')->form();
                    $val = $form->getValues();
                    if (!isset($val['source'])) {
                        break;
                    }
                    $url = sprintf('https://www.11880.com/suche?what=%s&where=%s&street=&lastname=&firstname=&firmen=1&personen=&page=%d&query=%s', strtolower($q), strtolower($town), $ta + 1, $val['source']);
                    $html = $browser->request('GET', $url);
                    $crawler = new Crawler();
                    $crawler->addHtmlContent($html->html());

//                    $button =  $crawler->selectButton('');
//                    $button->each(function ($a) {
//                        /** @var Crawler $a */
//                        if ($a->getUri() !== 'https://firma-eintragen-kostenlos.11880.com/new/portal_header') {
//
//                        dump($a->getUri());
//                        die;
//                        }#
//                    });
                    // $form = $button->form();
                    // $crawler = $browser->submit($form);
//                    dump($values);
//                    dump($form);
//                    die;
                } catch (\Exception $e) {
//                    dump('ERROR');
//                    dump($crawler->selectButton(''));
//                    dump(count($p));
                    // dump($e->getMessage());
                    // die;
                    break;
                }
            }

            $treffer = $crawler->filterXPath('//li[contains(@class,"result-list-entry")]');

//            dump(count($p));
//            dump($treffer);
            $p[] = $treffer->each(function ($e) use ($fp) {
                if ($e->filter('.result-list-entry-phone-number__label')->count()) {
                    $phone = $e->filter('.result-list-entry-phone-number__label')->text();
                    $name = $e->filter('a.result-list-entry-title')->attr('title');
                    /* @var Crawler $address */
                    try {
                        $address = $e->filter('.result-list-entry-address')->text();
                    } catch (\Exception $exception) {
                        $address = 'Nicht Angegeben';
                    }

                    fputcsv($fp, [$name, $address, $phone, '', '', '']);

                    return [$name, $address, $phone, '', '', ''];
                }

                return [];
            });
            ++$ta;
        }
        fclose($fp);

        $response = new Response(file_get_contents($filename));
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename1.'"');
        unset($filename);

        return $response;
    }

    #[Route(path: '/{crawl}/{cou}', name: 'backend_crawl_oeffentliche', methods: ['GET'])]
    public function crawl(string $crawl, int $cou = 1): Response
    {
        $zahl = 1000 + $cou;
        if ($crawl === 'q') {
            return $this->render('backend/crawl/search.html.twig', ['file' => '', 'towns' => self::TOWNS]);
        }

        $browser = new HttpBrowser(HttpClient::create());
//        ZIP CRAWLER
//        $html1 = $browser->request(
//            'GET',
//            'https://www.dasoertliche.de/Themen/Postleitzahlen/'.str_replace(' ', '%20', $town).'.html'
//        );
//
//        $crawler1 = new Crawler();
//        $crawler1->addHtmlContent($html1->html());
//
//        $rows = $crawler1->filterXPath('//tr')->each(function ($d) {
//            if ($d->filter('td')->count()) {
//                return $d->filter('td')->first()->text();
//            }
//
//            return false;
//        });

//        $zips = [
//            'Berlin' => ["10115","10117","10119","10178","10179","10243","10245","10247","10249","10315","10317","10318","10319","10365","10367","10369","10405","10407","10409","10435","10437","10439","10551","10553","10555","10557","10559","10585","10587","10589","10623","10625","10627","10629","10707","10709","10711","10713","10715","10717","10719","10777","10779","10781","10783","10785","10787","10789","10823","10825","10827","10829","10961","10963","10965","10967","10969","10997","10999","12043","12045","12047","12049","12051","12053","12055","12057","12059","12099","12101","12103","12105","12107","12109","12157","12159","12161","12163","12165","12167","12169","12203","12205","12207","12209","12247","12249","12277","12279","12305","12307","12309","12347","12349","12351","12353","12355","12357","12359","12435","12437","12439","12459","12487","12489","12524","12526","12527","12555","12557","12559","12587","12589","12619","12621","12623","12627","12629","12679","12681","12683","12685","12687","12689","13051","13053","13055","13057","13059","13086","13088","13089","13125","13127","13129","13156","13158","13159","13187","13189","13347","13349","13351","13353","13355","13357","13359","13403","13405","13407","13409","13435","13437","13439","13465","13467","13469","13503","13505","13507","13509","13581","13583","13585","13587","13589","13591","13593","13595","13597","13599","13627","13629","14050","14052","14053","14055","14057","14059","14089","14109","14129","14163","14165","14167","14169","14193","14195","14197","14199","16321","15378","15378","15345","15366","15562","15566","15754","23823"],
//        ];
        $filename1 = str_replace(' ', '_', $crawl);
        $filename1 = $filename1.'_'.$zahl.'.csv';
        $filename = './search/'.$filename1;
        $q = str_replace(' ', '%20', $crawl);
        $fp = fopen($filename, 'w+');
        fputcsv($fp, ['Seitenzähler', 'Firmen-Name', 'Adresse', 'Telefon', 'E-Mail', 'Notitz', 'Bearbeitet']);
        $falser = 0;
        $p = [];
        while ($cou <= $zahl) {
            // $url = 'https://www.dasoertliche.de/?zvo_ok=0&buc=&plz=&quarter=&district=&ciid=&kw='.$q.'&ci=&kgs=&buab=&zbuab=&form_name=search_nat&recFrom='.$cou++;
            $url = sprintf(
                'https://www.dasoertliche.de/?zvo_ok=0&buc=&plz=&quarter=&district=&ciid=&kw=%s&ci=&kgs=&buab=&zbuab=&form_name=search_nat&recFrom=%d',
                $q, $cou);
            $cou += 25;
            $html = $browser->request('GET', $url);
            $crawler = new Crawler();
            $crawler->addHtmlContent($html->html());
            $treffer = $crawler->filterXPath('//div[contains(@class,"st-treffer")]');

            $p[] = $treffer->each(function ($e) use ($cou) {
                /** @var Crawler $e */
                if ($e->filter('span.st-treff-name')->count() && $e->filter('span.st-rufnr-nm')->count()) {
                    $email = '';
                    $phone = $e->filter('span.st-rufnr-nm')->text();
                    $name = $e->filter('span.st-treff-name')->text();
                    $address = $e->filter('address')->text();
                    if (strstr($phone, '...')) {
                        return [];
                    }

                    return [$cou, $name, $address, $phone, $email, '', ''];
                }

                return [];
            });
        }

        $has = [];
        foreach ($p as $a) {
            foreach ($a as $b) {
                if (!empty($b) && is_array($b) && count($b) && !in_array($b[3], $has)) {
                    if (strstr((string) $b[3], '...')) {
                        ++$falser;
                    } else {
                        fputcsv($fp, [$b[0], $b[1], $b[2], $b[3], '', '']);
                        $has[] = $b[3];
                    }
                }
            }
        }
        fclose($fp);

        $response = new Response(file_get_contents($filename));
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename1.'"');
        unset($filename);

        return $response;
    }
}
