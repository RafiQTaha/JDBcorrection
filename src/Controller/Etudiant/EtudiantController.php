<?php

namespace App\Controller\Etudiant;

use App\Entity\EnseignantSemestre;
use App\Entity\Rapport;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/etudiant')]
class EtudiantController extends AbstractController
{
    private $client;
    private $em;
    public function __construct(HttpClientInterface $client, ManagerRegistry $em)
    {
        $this->client = $client;
        $this->em = $em->getManager();
    }
    #[Route('/', name: 'etudiant_index')]
    public function index(Request $request): Response
    {
        $table = null;
        if(in_array('ROLE_ENSEIGNANT', $this->getUser()->getRoles())) {
           $sessions = $this->em->getRepository(EnseignantSemestre::class)->getEnseignantSemestresByClosedTime($this->getUser());
        //    dd($sessions);
           return $this->render('etudiant/intro.html.twig', [
               'sessions' => $sessions
           ]);
        }
        // $response = $this->client->request('GET', $this->getParameter("api")."/getetablissement");
        // $etablissements = $response->toArray();
        return $this->redirectToRoute("rapport_index");
        // return $this->render('etudiant/index.html.twig', [
        //     'etablissements' => $etablissements,
        //     'table' => $table
        // ]);
    }
    #[Route('/list/{session}', name: 'etudiant_list')]
    public function rapports(EnseignantSemestre $session, Request $request): Response
    {
        $table = null;
        // dd($session);
        $table = $this->list($request, $session->getSemestre());
        return $this->render('etudiant/index.html.twig', [
            'table' => $table
        ]);
    }
    
    public function list($request, $id)
    {
        $response = $this->client->request('GET', $this->getParameter("api")."/getlistofstagebysemestreannee/$id");
        $session = $request->getSession();
        $data = $response->toArray();
        // dd($data);
        $inscriptions = $data['inscriptions'];
        $cycles = $data['cycles'];
        // dd($cycles);
        $data = [];
        foreach ($inscriptions as $key => $inscription) {
            // if($inscription['id'] == "12105" or $inscription['id'] == "12156") {
                $arrayOfStage = [];
                foreach ($cycles as $cycle) {
                    $stageClinique = $this->em->getRepository(Rapport::class)->findStage($inscription["id"], $cycle['stages'], "clinique");
                    $stageSimulation = $this->em->getRepository(Rapport::class)->findStage($inscription["id"], $cycle['stages'], "simulation");
                    $stagePharmacy = $this->em->getRepository(Rapport::class)->findStage($inscription["id"], $cycle['stages'], "pharmacy");
                    $stageSDentaire = $this->em->getRepository(Rapport::class)->findStage($inscription["id"], $cycle['stages'], "dentaire");
                    
                    array_push($arrayOfStage, [
                        'clinique' => $stageClinique,
                        'simulation' => $stageSimulation,
                        'pharmacy' => $stagePharmacy,
                        'dentaire' => $stageSDentaire
                    ]);
                }
                array_push($data, [
                    'inscription' => $inscription,
                    'stages' => $arrayOfStage
                ]);
            // }
        }
        // dd($cycles);
        $html = $this->render("rapport/page/table.html.twig", [
            'datas' => $data,
            'cycles' => $cycles
        ])->getContent();
        $session->set("inscriptions", $inscriptions);
        return $html;
    }
}
