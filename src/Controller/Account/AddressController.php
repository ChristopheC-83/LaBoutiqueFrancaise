<?php

namespace App\Controller\Account;

use App\Classe\Cart;
use App\Entity\Address;
use App\Form\AddressUserType;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;



class AddressController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/compte/adresses', name: 'app_account_addresses')]
    public function index(): Response
    {
        return $this->render('account/address/index.html.twig');
    }
    #[Route('/compte/adresses/delete/{id}', name: 'app_account_address_delete')]
    public function delete($id, AddressRepository $addressRepository): Response
    {

        // Récupération de l'adresse
        $address = $addressRepository->findOneById($id);

        // L'addresse existe ?
        // L'adresse appartient bien à cet utilisateur ?
        if (!$address or $address->getUser() != $this->getUser()) {
            $this->addFlash('warning', 'Adresse non répertoriée !');
            return $this->redirectToRoute('app_account_addresses');
        }

        $this->addFlash('info', 'Adresse supprimée !');
        $this->entityManager->remove($address);
        $this->entityManager->flush();
        return $this->redirectToRoute('app_account_addresses');
    }

    // Ajout d'une adresse si pas d'id
    // Modification d'une adresse si id
    #[Route('/compte/adresse/ajouter/{id}', name: 'app_account_address_form', defaults: ['id' => null])]
    public function form(Request $request, $id, AddressRepository $addressRepository, Cart $cart): Response
    {

        if ($id) {
            $address = $addressRepository->findOneById($id);
            //  L'addresse existe ?
            // L'adresse appartient bien à cet utilisateur ?
            if (!$address or $address->getUser() != $this->getUser()) {
                $this->addFlash('warning', 'Adresse non répertoriée !');
                return $this->redirectToRoute('app_account_addresses');
            }
        } else {
            $address = new Address();
            $address->setUser($this->getUser());
        }

        $form = $this->createForm(AddressUserType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($address);
            $this->entityManager->flush();
            $this->addFlash('success', 'Votre adresse a bien été sauvegardée ! ');

            if ($cart->fullQtt() > 0) {
                return $this->redirectToRoute('app_order');
            }
            return $this->redirectToRoute('app_account_addresses');
        }

        return $this->render('account/address/form.html.twig', [
            'addressForm' => $form
        ]);
    }







}