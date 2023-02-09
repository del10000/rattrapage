<?php

namespace App\Controller;


use App\Entity\Pret;
use App\Form\PretType;
use App\Repository\PretRepository;
use App\Service\MailerService;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/pret')]
class PretController extends AbstractController
{
    #[Route('/', name: 'app_pret_index', methods: ['GET'])]
    public function index(PretRepository $pretRepository): Response
    {
        return $this->render('pret/index.html.twig', [
            'prets' => $pretRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_pret_new', methods: ['GET', 'POST'])]
    public function new(Request $request, PretRepository $pretRepository, MailerService $mailer): Response
    {
        $pret = new Pret();
        $form = $this->createForm(PretType::class, $pret);
        $form->handleRequest($request);
        $interval = new \DateInterval('P15D');
        $date_rendu = new DateTime();
        $date_rendu->add($interval);
        $date_rendu->format('d-m-Y');

        if ($form->isSubmitted() && $form->isValid()) {
            $pret->setDatePret(new DateTime());
            $pret->setDateRendu($date_rendu);
            $pret->setStatus(1);

            if ($pret->getMateriel()->getEnPret() < $pret->getMateriel()->getnombreTotal()) {
                $pret->getMateriel()->setEnPret($pret->getMateriel()->getEnPret() + 1);
            }
            if ($pret->getMateriel()->getEnStock() > 0)
                $pret->getMateriel()->setEnStock($pret->getMateriel()->getEnStock() - 1);
            $pretRepository->save($pret, true);

            $emprunt = $pret->getDatePret()->format('d-m-Y');
            $rendu = $pret->getDateRendu()->format('d-m-Y');
            $matos = $pret->getMateriel()->getNom();
            $destinataire = $pret->getUserMail();
            $objet = 'Pret de matériel';
            $message = "Confirmation de votre emprunt :<br> matériel emprunté : $matos <br> Date de pret : $emprunt <br> Date de retour :  $rendu";

            $mailer->sendEmail($destinataire, $objet, $message);

            return $this->redirectToRoute('app_pret_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('pret/new.html.twig', [
            'pret' => $pret,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pret_show', methods: ['GET'])]
    public function show(Pret $pret): Response
    {
        return $this->render('pret/show.html.twig', [
            'pret' => $pret,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_pret_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Pret $pret, PretRepository $pretRepository): Response
    {
        // $form = $this->createForm(PretType::class, $pret);
        $form = $this->createFormBuilder($pret)
            ->add('user_name')
            ->add('materiel')
            ->add('status')
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($pret->isStatus() == false) {

                if ($pret->getMateriel()->getEnStock() < $pret->getMateriel()->getNombreTotal()) {
                    $pret->getMateriel()->setEnStock($pret->getMateriel()->getEnStock() + 1);
                }

                if ($pret->getMateriel()->getEnPret() > 0) {
                    $pret->getMateriel()->setEnPret($pret->getMateriel()->getEnPret() - 1);
                }
            }

            if ($pret->isStatus() == true) {

                if ($pret->getMateriel()->getEnStock() > 0) {
                    $pret->getMateriel()->setEnStock($pret->getMateriel()->getEnStock() - 1);
                }

                if ($pret->getMateriel()->getEnPret() < $pret->getMateriel()->getNombreTotal()) {
                    $pret->getMateriel()->setEnPret($pret->getMateriel()->getEnPret() + 1);
                }
            }
            $pretRepository->save($pret, true);

            return $this->redirectToRoute('app_pret_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pret/edit.html.twig', [
            'pret' => $pret,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id?}', name: 'app_pret_delete', methods: ['POST'])]
    public function delete(Request $request, Pret $pret, PretRepository $pretRepository): Response
    {

        if ($this->isCsrfTokenValid('delete' . $pret->getId(), $request->request->get('_token'))) {

            $pretRepository->remove($pret, true);
        }

        if ($pret->getMateriel()->getEnStock() < $pret->getMateriel()->getNombreTotal()) {
            $pret->getMateriel()->setEnStock($pret->getMateriel()->getEnStock() + 1);

            $pretRepository->save($pret, true);
        }

        if ($pret->getMateriel()->getEnPret() > 0) {
            $pret->getMateriel()->setEnPret($pret->getMateriel()->getEnPret() - 1);

            $pretRepository->save($pret, true);
        }

        return $this->redirectToRoute('app_pret_index', [], Response::HTTP_SEE_OTHER);
    }
}
