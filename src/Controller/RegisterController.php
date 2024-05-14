<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegisterUserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RegisterController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegisterUserType::class, $user);

        $form->handleRequest($request);  // écoute la request pour continuer

        // si formulaire soumis 
        if ($form->isSubmitted() && $form->isValid()) {

            // dd($form->getData());
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Compte créé ! Vous pouvez vous connecter !');
            return $this->redirectToRoute('app_login');
           
        }



        // ->DB 


        // -> notification


        return $this->render('register/index.html.twig', [
            'registerForm' => $form->createView(),
        ]);
    }
}