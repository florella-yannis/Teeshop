<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    #[Route('/admin/ajouter-un-produit', name: 'create_product', methods:['GET', 'POST'])]
    public function createProduct(Request $request, ProductRepository $repo): Response
    {
        $product = new Product();

        
        return $this->render('', [
            '' => '',
        ]);
    }
}
