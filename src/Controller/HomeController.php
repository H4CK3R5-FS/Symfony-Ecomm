<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\Produit;
use App\Form\ContactType;
use App\Entity\Commentaire;
use App\Form\RechercheType;
use App\Form\NewProductType;
use App\Service\CartService;
use App\Form\CommentaireType;
use App\Repository\UserRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CommentaireRepository;
use App\Notifications\ContactNotification;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;


class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {


        return $this->render('home/index.html.twig', []);
    }

    #[Route('/home', name: 'app_homes')]
    public function home(ProduitRepository $repo, Request $request): Response
    {
        $form = $this->createForm(RechercheType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->get('recherche')->getData();
            $products = $repo->getProductbyname($data);
        } else {
            $products = $repo->findAll();
        }



        return $this->render('home/home.html.twig', [
            'products' => $products,
            'formRecherche' => $form->createView()
        ]);
    }


    #[Route('/home/new', name: "new_article")]
    #[Route('/home/edit/{id}', name: "edit_article", requirements: ["id" => "\d+"])]
    public function forms(Request $rq, EntityManagerInterface $manager, Produit $produits = null, UserRepository $user)
    {
        if (!$produits) {
            $produits = new Produit;
        }
        $User = $this->getUser()->getUsername();
        $data = $user->findOneBy([
            'email' => $User
        ]);
        $form = $this->createForm(NewProductType::class, $produits);
        $form->handleRequest($rq);

        if ($form->isSubmitted() && $form->isValid()) {
            $produits->setUser($data);
            $manager->persist($produits);
            $manager->flush();
            $this->addFlash('success1', "Succès maintenant votre produit est en ligne !");
            return $this->redirectToRoute('show', [
                'id' => $produits->getId()
            ]);
        }
        return $this->render("home/newArticl.html.twig", [
            'editmod' => $produits->getId() !== null,
            'formProd' => $form->createView()
        ]);
    }
    #[Route("/home/show/{id}", name: "show", requirements: ["id" => "\d+"])]
    public function show(Request $rq, EntityManagerInterface $manager, Produit $articles, UserRepository $us, CommentaireRepository $allCmt,ProduitRepository $prod, $id): Response
    {

        $test = $prod->find($id);

        $user = $us->findOneBy([
            'id' => $test->getUser()
        ]);
        $cmt = new Commentaire;
        $cmt->setCreatedAt(new \DateTime());

        $dataProd = $prod->find($id);

        $form = $this->createForm(CommentaireType::class, $cmt);
        $form->handleRequest($rq);

        if ($form->isSubmitted() && $form->isValid()) {
            $User = $this->getUser()->getUsername();
            $data = $us->findOneBy([
                'email' => $User
            ]);
            $cmt->setIdProduit($dataProd);
            $cmt->setUser($data);
            $manager->persist($cmt);
            $manager->flush();

            
            
            return $this->redirectToRoute('show', [
                "id" => $id,
                
            ]);
        }
        $all = $allCmt->findBy([
            "produit" => $id
        ]);
        return $this->render("home/show.html.twig", [
            'article' => $articles,
            'data' => $user,
            'id' => $id,
            "all" => $all,
            'formCom' => $form->createView()
        ]);
    }
    #[Route('/home/contact', name: 'ecom_contact')]
    public function contact(Request $request, EntityManagerInterface $manager, ContactNotification $cn)
    {
        $contact = new Contact;
        $contact->setCreatedAt(new \DateTime());
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($contact);
            $manager->flush();
            $cn->notify($contact);
            $this->addFlash('success2', "message envoyé avec succès !");
            return $this->redirectToRoute('ecom_contact');
        }
        return $this->render('home/contact.html.twig', [
            'formContact' => $form->createView()
        ]);
    }
    #[Route('/home/mesproduits', name: 'ecom_mesproduits')]
    public function mesprod(Request $request, UserRepository $us, ProduitRepository $prod)
    {
        $user = $this->getUser()->getUsername();
        $data = $us->findOneBy([
            'email' => $user
        ]);
            
        $last = $prod->findBy([
            'user'=>$data
        ]);

        
        return $this->render('home/mesprod.html.twig', [
            'datas'=>$last
        ]);
    }
    #[Route('/home/panier', name: 'app_cart')]
    public function panier(RequestStack $rs, ProduitRepository $repo): Response
    {
        $session = $rs->getSession();
        $cart = $session->get('cart', []);
        $qt = 0;

        $cartWithData = [];
        if ($cart == null) {
            return $this->render('home/panier.html.twig', [
                'items' => $cartWithData,
                
            ]);
        }
        foreach($cart as $id => $quantity)
        {
            $cartWithData[] = [
                'produit' => $repo->find($id),
                'quantity' => $quantity
            ];
            $qt += $quantity;
        }
        $session->set('qt', $qt);
        $total = 0;

        foreach ($cartWithData as $item) {
            $totalItem = $item['produit']->getPrix() * $item['quantity'];
            $total += $totalItem;
        }
        return $this->render('home/panier.html.twig', [
            'items' => $cartWithData,
            'total' => $total
        ]);
    }
    #[Route("/cart/add/{id}", name:"cart_add")]
    public function add($id, CartService $cs)
    {
        $cs->add($id);
        return $this->redirectToRoute('app_cart');
    }

    #[Route("/cart/remove/{id}", name:"cart_remove")]
    public function remove($id, RequestStack $rs, )
    {
        $session = $rs->getSession();
        $cart = $session->get('cart',[]);

        if (!empty($cart[$id])) {
            unset($cart[$id]);
        }
        $session->set('cart', $cart);
        return $this->redirectToRoute('app_cart');
    }
    #[Route("/cart/remove1/{id}", name:"cart_remove1")]
    public function remove1($id, RequestStack $rs,CartService $cs)
    {
        $cs->removeCart($id);
        
        
        return $this->redirectToRoute('app_cart');
    }
    #[Route("/home/payement", name:"pay")]
    public function pay(RequestStack $rs)
    {
        $session = $rs->getSession();
        $session->remove('cart');
        $session->remove('qt');

         
        
        return $this->render("home/payement.html.twig");
    }
    
    
}
