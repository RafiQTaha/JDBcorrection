<?php

namespace App\Controller\Etudiant;

use App\Entity\User;
use Symfony\Component\Mime\Email;
use App\Entity\EnseignantSemestre;
use App\Controller\DatatablesController;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Util\Json;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/etudiant/session')]
class SessionController extends AbstractController
{
    private $client;
    private $em;
    public function __construct(HttpClientInterface $client, ManagerRegistry $em)
    {
        $this->client = $client;
        $this->em = $em->getManager();
    }
    #[Route('/', name: 'session_index')]
    public function index(): Response
    {
        $response = $this->client->request('GET', $this->getParameter("api")."/getetablissement");
        $etablissements = $response->toArray();
        $enseignants = $this->em->getRepository(User::class)->findUsersByRole('ROLE_ENSEIGNANT');
        // dd($enseignants);
        return $this->render('session/index.html.twig', [
            'etablissements' => $etablissements,
            'enseignants' => $enseignants
        ]);
    }
    #[Route('/list', name: 'session_list')]
    public function list(Request $request): Response
    {
        $params = $request->query;
        // dd($params);
        $where = $totalRows = $sqlRequest = "";
        $filtre = "where 1 = 1";   
        // dd($params->get('columns')[0]);
            
        $columns = array(
            array( 'db' => 'es.id','dt' => 0),
            array( 'db' => 'es.session','dt' => 1),
            array( 'db' => 'DATE_FORMAT(es.close_date, "%d-%m-%Y")','dt' => 2),
            array( 'db' => 'u.username','dt' => 3),
            array( 'db' => 'u.nom','dt' => 4),
            array( 'db' => 'u.prenom','dt' => 5),
            array( 'db' => 'u.enable','dt' => 6),
           
           
            
        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        
        FROM user u 
        inner join enseignant_semestre es on es.user_id = u.id
        
        $filtre "
        ;
        // dd($sql);
        $totalRows .= $sql;
        $sqlRequest .= $sql;
        $stmt = $this->em->getConnection()->prepare($sql);
        $newstmt = $stmt->executeQuery();
        $totalRecords = count($newstmt->fetchAll());
        // dd($sql);
            
        // search 
        $where = DatatablesController::Search($request, $columns);
        if (isset($where) && $where != '') {
            $sqlRequest .= $where;
        }
        $sqlRequest .= DatatablesController::Order($request, $columns);
        // dd($sqlRequest);
        $stmt = $this->em->getConnection()->prepare($sqlRequest);
        $resultSet = $stmt->executeQuery();
        $result = $resultSet->fetchAll();
        
        
        $data = array();
        // dd($result);
        $i = 1;
        foreach ($result as $key => $row) {
            $nestedData = array();
            $cd = $row['id'];
            // dd($row);
            
            foreach (array_values($row) as $key => $value) {
                $nestedData[] = $value;                
            }
            $nestedData["DT_RowId"] = $cd;
            $nestedData["DT_RowClass"] = $cd;
            $data[] = $nestedData;
            $i++;
        }
        // dd($data);
        $json_data = array(
            "draw" => intval($params->get('draw')),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalRecords),
            "data" => $data   
        );
        // die;
        return new Response(json_encode($json_data));

    }
    #[Route('/generer', name: 'session_generer')]
    public function generer(Request $request, MailerInterface $mailer): Response
    {
        $codeSession = $this->generateRandomString();
        $user = $this->em->getRepository(User::class)->find($request->get('enseignant'));

        $session = $this->em->getRepository(EnseignantSemestre::class)->findByUserAndSemestre($user, $request->get("semestre"));
        if($session){
            return new JsonResponse("Session déja crée pour cette semestre",500);
        }
        $session = new EnseignantSemestre();

        $session->setSemestre($request->get("semestre"));
        $session->setSession($codeSession);
        $session->setDesignation($request->get("designation"));
        $stop_date = new \DateTime('now');
        $stop_date->modify('+7 day');
        $session->setCloseDate($stop_date);
        $session->setUser($user);
        $this->em->persist($session);

        $this->em->flush();
        $email = (new Email())
            ->from('no-reply@uiass.ma')
            ->to($user->getEmail())
            //->cc('cc@example.com')
            //->bcc('bcc@example.com')
            //->replyTo('fabien@example.com')
            //->priority(Email::PRIORITY_HIGH)
            ->subject('Nouvelle Session!')
            // ->text('
                

            // ');
            ->html('
            <p>
            Bonjour '.$user->getNom() .' '.$user->getPrenom().',
            </p>
            <p>
                Vous avez une nouvelle session à corriger.
                Consultet le lien suivant <a href="https://jdb-correction.mtsi-test.com" >Lien</a>
            </p>');

        $mailer->send($email);

        return new JsonResponse("Bien Enregistre");
    }

    public function generateRandomString($length = 6) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}