<?php

namespace App\Controller\Etudiant;

use ZipArchive;
use SplFileObject;
use App\Entity\Rapport;
use Selective\Rar\RarFileReader;
use Doctrine\Persistence\ManagerRegistry;
use phpDocumentor\Reflection\Types\This;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[Route('/etudiant/rapport')]
class RapportController extends AbstractController
{
    private $client;
    private $em;
    public function __construct(HttpClientInterface $client, ManagerRegistry $em)
    {
        $this->client = $client;
        $this->em = $em->getManager();
    }
    #[Route('/', name: 'rapport_index')]
    public function index(): Response
    {
        $response = $this->client->request('GET', $this->getParameter("api")."/getetablissement");
        $etablissements = $response->toArray();
        return $this->render('rapport/index.html.twig', [
            'etablissements' => $etablissements
        ]);
    }
    #[Route('/import', name: 'rapport_import')]
    public function import(Request $request, SluggerInterface $slugger): Response
    {
        $files = $request->files->get('file');
        foreach ($files as $file) {
            if($file->guessExtension() !== 'pdf'){
                return new JsonResponse('Prière d\'enregister des fichiers pdf!', 500);            
            }
        }
        
        foreach ($files as $file) {
            $name = $file->getClientOriginalName();
            $data = explode("_", $name);
            $stage = $data[1];
            $inscription = $data[2];
            $type = $data[3];

            $originalFilename = pathinfo($name, PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
            
            try {
                $file->move(
                    $this->getParameter('rapport_directory'),
                    $newFilename
                );
                $rapportExist = $this->em->getRepository(Rapport::class)->findOneBy(['stage' => $stage, 'inscription' => $inscription, 'type' => $type]);
                if($rapportExist){
                    $rapportExist->setUrl($newFilename);
                    $rapportExist->setDateUpdated(new \DateTime("now"));
                } else {
                    $rapport = new Rapport();
                    $rapport->setStage($stage);
                    $rapport->setInscription($inscription);
                    $rapport->setType($type);
                    $rapport->setDateCreated(new \DateTime("now"));
                    $rapport->setDateUpdated(new \DateTime("now"));
                    $rapport->setUrl($newFilename);
                    $this->em->persist($rapport);
                }
                $this->em->flush();
            } catch (FileException $e) {
                return new JsonResponse($e, 500);
            }
        }
        
        return new JsonResponse("Bien enregistre");
    }
    #[Route('/list/{id}', name: 'rapport_list')]
    public function list(Request $request, $id): Response
    {
        $response = $this->client->request('GET', $this->getParameter("api")."/getlistofstagebysemestreannee/$id");
        $session = $request->getSession();
        $data = $response->toArray();
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
                    
                    array_push($arrayOfStage, [
                        'clinique' => $stageClinique,
                        'simulation' => $stageSimulation
                    ]);
                }
                array_push($data, [
                    'inscription' => $inscription,
                    'stages' => $arrayOfStage
                ]);
            // }
        }
        $html = $this->render("rapport/page/table.html.twig", [
            'datas' => $data,
            'cycles' => $cycles
        ])->getContent();
        $session->set("inscriptions", $inscriptions);
        return new JsonResponse($html);
    }
    #[Route('/stageDetails/{rapport}', name: 'rapport_stageDetails')]
    public function stageDetails(Request $request, Rapport $rapport): Response
    {
        $session = $request->getSession();
        $inscriptions = $session->get("inscriptions");
        $inscription = $inscriptions[array_search($rapport->getInscription(), array_column($inscriptions, 'id'))];
        // dd($inscription);
        return $this->render("rapport/detail.html.twig", [
            'rapport' => $rapport,
            'inscription' => $inscription
        ]);
    }
    #[Route('/rapportsave/{rapport}', name: 'rapport_rapport_save')]
    public function rapport_save(Request $request, Rapport $rapport): Response
    {
        // dd($request);
        if(!in_array('ROLE_ENSEIGNANT', $this->getUser()->getRoles())) {
            throw new AccessDeniedException("Vous avez pas le droit!");
        }
        $rapport->setNote(floatval($request->get('note')));
        $rapport->setObservation($request->get('observation'));
        $this->em->flush();
        return $this->redirectToRoute('rapport_stageDetails', [
            'rapport' => $rapport->getId()
        ]);
    }

    
    #[Route('/export_etat_Notes/{semestre}', name: 'export_etat_Notes')]
    public function export_etat_Notes(Request $request, $semestre)
    {
        $response = $this->client->request('GET', $this->getParameter("api")."/getlistofstagebysemestreannee/$semestre")->toArray();
        $abreviations = $this->client->request('GET', $this->getParameter("api")."/getabreviationsbysemestre/$semestre")->toArray();
        // dd($abreviations);
        $inscriptions = $response['inscriptions'];
        $cycles = $response['cycles'];
        // dd($inscriptions);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Etablissement');
        $sheet->setCellValue('B1', 'Formation');
        $sheet->setCellValue('C1', 'Promotion');
        $sheet->setCellValue('D1', 'ASemestre');
        $sheet->setCellValue('E1', 'Module');
        $sheet->setCellValue('F1', 'Élément de Module');
        $sheet->setCellValue('G1', 'Type épreuve');
        $sheet->setCellValue('H1', 'Date');
        $sheet->setCellValue('I1', 'Enseignant');
        
        $sheet->setCellValue('A2', $abreviations['etablissement']);
        $sheet->setCellValue('B2', $abreviations['formation']);
        $sheet->setCellValue('C2', $abreviations['promotion']);
        $sheet->setCellValue('D2', $abreviations['semestre']);
        $sheet->setCellValue('E2', '');
        $sheet->setCellValue('F2', '');
        $sheet->setCellValue('G2', '');
        $sheet->setCellValue('H2', '');
        $sheet->setCellValue('I2', '');

        $sheet->setCellValue('A4', 'ORD');
        $sheet->setCellValue('B4', 'CODE');
        $sheet->setCellValue('C4', 'NOM');
        $sheet->setCellValue('D4', 'PRENOM');
        $sheet->setCellValue('E4', 'CYCLE 1');
        $sheet->setCellValue('F4', 'N-Cli');
        $sheet->setCellValue('G4', 'N-Sim');
        $sheet->setCellValue('H4', 'OBS Cli');
        $sheet->setCellValue('I4', 'OBS Sim');
        $sheet->setCellValue('J4', 'CYCLE 2');
        $sheet->setCellValue('K4', 'N-Cli');
        $sheet->setCellValue('L4', 'N-Sim');
        $sheet->setCellValue('M4', 'OBS Cli');
        $sheet->setCellValue('N4', 'OBS Sim');
        $sheet->setCellValue('O4', 'CYCLE 3');
        $sheet->setCellValue('P4', 'N-Cli');
        $sheet->setCellValue('Q4', 'N-Sim');
        $sheet->setCellValue('R4', 'OBS Cli');
        $sheet->setCellValue('S4', 'OBS Sim');
        $sheet->setCellValue('T4', 'CYCLE 4');
        $sheet->setCellValue('U4', 'N-Cli');
        $sheet->setCellValue('V4', 'N-Sim');
        $sheet->setCellValue('W4', 'OBS Cli');
        $sheet->setCellValue('X4', 'OBS Sim');

        $i=5;
        $j=1;
        foreach ($inscriptions as $inscription) {
            // dd($infoBYinscription);
            $sheet->setCellValue('A'.$i, $j);
            $sheet->setCellValue('B'.$i, $inscription['id']);
            $sheet->setCellValue('C'.$i, $inscription['nom']);
            $sheet->setCellValue('D'.$i, $inscription['prenom']);
            $k = 1;
            foreach ($cycles as $cycle) {
                $stageClinique = $this->em->getRepository(Rapport::class)->findStage($inscription["id"], $cycle['stages'], "clinique");
                $stageSimulation = $this->em->getRepository(Rapport::class)->findStage($inscription["id"], $cycle['stages'], "simulation");
                if ($stageClinique != null && $stageSimulation != null) {
                    switch ($k) {
                        case 1:
                            $sheet->setCellValue('E'.$i, $stageClinique->getStage());
                            $sheet->setCellValue('F'.$i, $stageClinique->getNote());
                            $sheet->setCellValue('G'.$i, $stageSimulation->getNote());
                            $sheet->setCellValue('H'.$i, $stageClinique->getObservation());
                            $sheet->setCellValue('I'.$i, $stageSimulation->getObservation());
                            break;
                        case 2:
                            $sheet->setCellValue('E'.$i, $stageClinique->getStage());
                            $sheet->setCellValue('F'.$i, $stageClinique->getNote());
                            $sheet->setCellValue('G'.$i, $stageSimulation->getNote());
                            $sheet->setCellValue('H'.$i, $stageClinique->getObservation());
                            $sheet->setCellValue('I'.$i, $stageSimulation->getObservation());
                            break;
                        case 3:
                            $sheet->setCellValue('E'.$i, $stageClinique->getStage());
                            $sheet->setCellValue('F'.$i, $stageClinique->getNote());
                            $sheet->setCellValue('G'.$i, $stageSimulation->getNote());
                            $sheet->setCellValue('H'.$i, $stageClinique->getObservation());
                            $sheet->setCellValue('I'.$i, $stageSimulation->getObservation());
                            break;
                        case 4:
                            $sheet->setCellValue('E'.$i, $stageClinique->getStage());
                            $sheet->setCellValue('F'.$i, $stageClinique->getNote());
                            $sheet->setCellValue('G'.$i, $stageSimulation->getNote());
                            $sheet->setCellValue('H'.$i, $stageClinique->getObservation());
                            $sheet->setCellValue('I'.$i, $stageSimulation->getObservation());
                            break;
                    }
                }
                $k++;
            }

            $i++;
            $j++;
        }
        $writer = new Xlsx($spreadsheet);
        $fileName = 'Extraction Des Articles.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }
    
}
