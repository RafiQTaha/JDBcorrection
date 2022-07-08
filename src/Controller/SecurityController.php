<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, ManagerRegistry $doctrine): Response
    {
        // dd($request);
        $userExist = $doctrine->getRepository(User::class)->findOneBy(['username' => $request->get('username')]);
        if($userExist){
            return new JsonResponse('Username déja exist !', 500);
        }
        $user = new User();
        // $user->setEmail($request->get('email'));
        $user->setUsername("admin");
        // $user->setUsername($request->get('username'));
        $user->setEnable(0);
        $user->setPassword($passwordHasher->hashPassword(
            $user,
            "0123456789"
        ));
        // $user->setPassword($passwordHasher->hashPassword(
        //     $user,
        //     $request->get('password')
        // ));
        $user->setRoles(['ROLE_ADMIN']);
        
        $doctrine->getManager()->persist($user);
        $doctrine->getManager()->flush();

        return new JsonResponse('good');
    }

    // #[Route(path: '/session', name: 'app_session')]
    // public function session(Request $request, TokenStorageInterface $TokenInterface, ManagerRegistry $doctrine)
    // {
    //     $em = $doctrine->getManager();
    //     $username = $request->get('username');
    //     $user = $em->getRepository(User::class)->findOneBy(['username' => $username]);
    //     if(!$user){
    //         return new JsonResponse("Invalide token",500);
    //     }
    //     if($user->getClosedDate() < new \Datetime("now")) {
    //         return new JsonResponse("Votre token est expiré",500);
    //     }
    //     if(!$user->getEnable()) {
    //         return new JsonResponse("Votre token est desactiver",500);
    //     }
    //     $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
    //     $TokenInterface->setToken($token);
    //     // $TokenInterface->setUser($user);
        
    //     // $TokenInterface->setAuthenticated(true);
    //     // $recipient = new Recipient($user->get());
    //     dd($this->getUser());
    // }
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
