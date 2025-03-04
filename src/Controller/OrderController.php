<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Entity\Order;
use App\Entity\OrderDetail;
use App\Form\OrderType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OrderController extends AbstractController
{

    // 1ere étape du tunnel d'achat
    //  choix addresse livraison
    // choix transporteur

    #[Route('/commande/livraison', name: 'app_order')]
    public function index(): Response
    {
        $addresses = $this->getUser()->getAddresses();

        if (count($addresses) == 0) {
            $this->addFlash('warning', 'Vous devez ajouter une adresse pour passer commande');
            return $this->redirectToRoute('app_account_address_form');
        }

        $form = $this->createForm(OrderType::class, null, [
            'addresses' => $addresses,
            // qd tu es soumis, changer d'url 
            'action' => $this->generateUrl('app_order_summary')
        ]);




        return $this->render('order/index.html.twig', [
            'deliverForm' => $form->createView()

        ]);
    }
    // 2eme étape du tunnel d'achat
    // recapitulatif de la commande
    // insertion en DB
    // preparation du paiement vers stripe

    #[Route('/commande/recapitulatif', name: 'app_order_summary')]
    public function add(Request $request, Cart $cart, EntityManagerInterface $entityManager): Response
    {
        if($request->getMethod() !== 'POST') {
            return $this->redirectToRoute('app_cart');
        }

        $products = $cart->getCart();

        $form = $this->createForm(OrderType::class, null, [
            'addresses' => $this->getUser()->getAddresses(),
        ]);

        $form->handleRequest($request);

        // dd($form->getData());

        //  création de la chaine adresse
        $addressObject = $form->get('addresses')->getData();
        $address= $addressObject->getFirstname() . ' ' . $addressObject->getLastname() . '<br>' ;
        $address .= $addressObject->getAddress() . '<br>';
        $address .= $addressObject->getPostal() . ' ' . $addressObject->getCity() .'<br>';
        $address .= $addressObject->getCountry() . '<br>';
        $address .= $addressObject->getPhone();
        // dd($address);
        // dd($cart);

     


        if($form->isSubmitted() && $form->isValid()) {
            $order = new Order();
            $order->setUser($this->getUser());
            $order->setCreatedAt(new \DateTime());
            $order->setState(1);
            $order->setCarrierName($form->get('carriers')->getData()->getName());
            $order->setCarrierPrice($form->get('carriers')->getData()->getPrice());
            $order->setDelivry($address);

            foreach($products as $product) {
                $orderDetail = new OrderDetail();
                $orderDetail->setProductName($product['object']->getName());
                $orderDetail->setProductIllustration($product['object']->getIllustration());
                $orderDetail->setProductPrice($product['object']->getPrice());
                $orderDetail->setProductTVA($product['object']->getTVA());
                $orderDetail->setProductQuantity($product['qtt']);
                $order->addOrderDetail($orderDetail);
            }

            $entityManager->persist($order);
            $entityManager->flush();


        }

        $totalHt = $cart->getTotal();
        // dd($totalHt);

        return $this->render('order/summary.html.twig', [
            'choices' => $form->getData(),
            'cart' => $products,
            'totalHt' => $cart->getTotal(),
            'totalWt' => $cart->getTotalWt(),
            'order' => $order,

        ]);
    }
}
