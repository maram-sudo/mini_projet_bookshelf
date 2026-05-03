<?php

namespace App\Controller;

use App\Entity\Livre;
use App\Form\LivreType;
use App\Repository\LivreRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/livres')]
class LivreController extends AbstractController
{
    #[Route('', name: 'livre_index', methods: ['GET'])]
    public function index(LivreRepository $livreRepository): Response
    {
        return $this->render('livre/index.html.twig', [
            'livres' => $livreRepository->findAll(),
        ]);
    }

    #[Route('/nouveau', name: 'livre_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_BIBLIOTHECAIRE')]
    public function new(Request $request, EntityManagerInterface $em, FileUploader $fileUploader): Response
    {
        $livre = new Livre();
        $form = $this->createForm(LivreType::class, $livre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $livre->setImageName($fileUploader->upload($imageFile));
            }
            $livre->setAjoutePar($this->getUser());
            $em->persist($livre);
            $em->flush();

            $this->addFlash('success', 'Livre ajouté avec succès !');
            return $this->redirectToRoute('livre_index');
        }

        return $this->render('livre/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'livre_show', methods: ['GET'])]
    public function show(Livre $livre): Response
    {
        return $this->render('livre/show.html.twig', [
            'livre' => $livre,
        ]);
    }

    #[Route('/{id}/modifier', name: 'livre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Livre $livre, EntityManagerInterface $em, FileUploader $fileUploader): Response
    {
        if ($this->getUser() !== $livre->getAjoutePar() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(LivreType::class, $livre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                if ($livre->getImageName()) {
                    $fileUploader->remove($livre->getImageName());
                }
                $livre->setImageName($fileUploader->upload($imageFile));
            }
            $em->flush();

            $this->addFlash('success', 'Livre modifié avec succès !');
            return $this->redirectToRoute('livre_index');
        }

        return $this->render('livre/edit.html.twig', [
            'form' => $form->createView(),
            'livre' => $livre,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'livre_delete', methods: ['POST'])]
    public function delete(Request $request, Livre $livre, EntityManagerInterface $em, FileUploader $fileUploader): Response
    {
        if ($this->getUser() !== $livre->getAjoutePar() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$livre->getId(), $request->request->get('_token'))) {
            if ($livre->getImageName()) {
                $fileUploader->remove($livre->getImageName());
            }
            $em->remove($livre);
            $em->flush();
            $this->addFlash('success', 'Livre supprimé avec succès !');
        }

        return $this->redirectToRoute('livre_index');
    }
}
