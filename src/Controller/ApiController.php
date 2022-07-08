<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api')]
class ApiController extends AbstractController
{
    private $client;
    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }
    #[Route('/formation/{id}', name: 'getformation')]
    public function getformation($id): Response
    {
        $request = $this->client->request('GET', $this->getParameter("api")."/getformation/$id");
        $formations = $request->toArray();
        $data = self::dropdown($formations,'Formation');
        return new JsonResponse($data);
    }
    #[Route('/promotion/{id}', name: 'getpromotion')]
    public function getpromotion($id): Response
    {
        $request = $this->client->request('GET', $this->getParameter("api")."/getpromotion/$id");
        $promotions = $request->toArray();
        $data = self::dropdown($promotions,'Promotion');
        return new JsonResponse($data);
    }
    #[Route('/semestre/{id}', name: 'getsemestre')]
    public function getsemestre($id): Response
    {
        $request = $this->client->request('GET', $this->getParameter("api")."/getsemestre/$id");
        $semestres = $request->toArray();
        $data = self::dropdown($semestres,'Semestre');
        return new JsonResponse($data);
    }

    static function dropdown($objects,$choix)
    {
        $data = "<option selected enabled value=''>Choix ".$choix."</option>";
        foreach ($objects as $object) {
            $data .="<option value=".$object['id'].">".$object['designation']."</option>";
        }
        return $data;
    }
}

