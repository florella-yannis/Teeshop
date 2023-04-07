<?php

namespace App\Controller;

use DateTime;
use App\Entity\Product;
use App\Form\ProductFormType;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

#prefixer tout nos url , ttres utile pour le controle d'acces voir security.yaml
#[Route('/admin')]
class ProductController extends AbstractController
{
    #[Route('/ajouter-un-produit', name: 'create_product', methods:['GET', 'POST'])]
    public function createProduct(Request $request, ProductRepository $repository, SluggerInterface $slugger): Response
    {
        $product = new Product();

        $form = $this->createForm(ProductFormType::class,$product)
        ->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()){

            $product->setCreatedAt(new DateTime());
            $product->setUpdatedAt(new DateTime());

            #on variabilise le fichier de la photo en récupérant les données du formulaire (input photo)
            #on obtient un objet de type uploadedFile()
            $photo = $form->get('photo')->getdata();
            # dd($photo)

            if($photo){

                $this->handleFile($product, $photo, $slugger);
            }// end if ($photo)

            $repository->save($product, true);

            $this->addFlash('succes', "Le produit est en ligne avec succès.");
            return $this->redirectToRoute('show_dashboard');

        }//end if $form

        return $this->render('admin/product/form.html.twig', [
            'form' => $form->createView()
            ]);
    }

    #[Route('/modifier-un-produit/{id}', name: 'update_product', methods:['GET', 'POST'])]
    public function updateProduct(Product $product, Request $request, ProductRepository $repository, SluggerInterface $slugger):Response
    {

        #recup de la photo actuelle
        $currentPhoto = $product->getPhoto();

        $form= $this->createForm(ProductFormType::class, $product, [
            'photo' => $currentPhoto
        ])
        ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $product->setUpdatedAt(new DateTime());
            $newPhoto = $form->get('photo')->getData();

            if($newPhoto){
                $this->handleFile($product, $newPhoto, $slugger);
            }else{
                $product->setPhoto($currentPhoto);
            }
            
            $repository->save($product, true);

            $this->addFlash('succes', "La modification a bien été enregistré");
            return $this->redirectToRoute('show_dashboard');
        }

        return $this->render('admin/product/form.html.twig', [
            'form' => $form->createView(),
            'product' => $product
        ]);
    }


    #[Route('/archiver-un-produit/{id}', name: 'soft_delete_product', methods:['GET'])]
    public function softDeleteProduct(Product $product, ProductRepository $repository): Response
    {
        $product->setDeletedAt(new DateTime());
        $repository-> save($product,true);

        $this->addFlash('success', "le produit a bien été archivé");
        return $this->redirectToRoute('show_dashboard');
    }

    private function handleFile(Product $product, UploadedFile $photo, SluggerInterface $slugger)
    {
        # 1 - Déconstruire le nom du fichier
                # a - variabiliser l'extension
                $extension = '.' . $photo->guessExtension();

                # 2 - assainir le nom du fichier(c a d retirer les accents et les espaces blancs)
                $safeFilename = $slugger->slug(pathinfo($photo->getClientOriginalName(),PATHINFO_FILENAME));

                # 3 - rendre le nom du fichier unique
                # a - reconstruire le nom du fichiet

            $newFileName = $safeFilename . '_' . uniqid() . $extension;

                # 4 - déplacer le fichier(upload dans notre app symfony)
                    //  dans le config > service.yaml           parameters:
                    // uploads_dir: '%kernel.project_dir%/public/uploads'
                #on utilise try/catch lorsqu'une méthode lance (throw) une exception(erreur)
                # On a défini un paramètre dans config/service.yaml qui est le chemin (absolu) du dossier 'uploads'
                # On récupère la valeur (le paramètre) avec getParameter() et le nom du param défini dans le fichier service.yaml.

                try{
                    $photo->move($this->getParameter('uploads_dir'), $newFileName);
                    # Si tout s'est bien passé (aucune Exception lancée) alors on doit set le nom de la photo en BDD
                    $product->setPhoto($newFileName);
                }
                catch(FileException $exception){
                    $this->addFlash('warning', "le fichier photo ne s'est pas importé correctement. Veuillez réessayer." . $exception->getMessage());
                }// end catch
    }
}



