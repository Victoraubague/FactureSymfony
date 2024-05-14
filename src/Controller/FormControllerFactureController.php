<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use TCPDF;

class FormControllerFactureController extends AbstractController
{
    #[Route('/form/controller/facture', name: 'app_form_controller_facture', methods: ['GET', 'POST'])]
    public function form(Request $request): Response
    {
        // Récupérer les données du formulaire
        $numero = $request->request->get('numero');
        $age = $request->request->get('age');

        // Si le formulaire a été soumis
        if ($numero !== null && $age !== null) {
            // Générer le PDF avec les données soumises
            $pdfContent = $this->generatePdf($numero, $age);

            // Enregistrer le PDF sur le serveur
            $pdfPath = $this->savePdf($pdfContent);

            // Rediriger vers la page de confirmation avec le chemin du PDF
            return $this->redirectToRoute('app_confirmation', ['pdf_path' => $pdfPath]);
        }

        // Afficher le formulaire
        return $this->render('form_controller_facture/index.html.twig');
    }

    private function generatePdf(string $numero, int $age): string
    {
        // Création d'une nouvelle instance de TCPDF
        $pdf = new TCPDF();

        // Ajout d'une nouvelle page
        $pdf->AddPage();

        //NOM / FACTURE OU DEVIS
        $pdf->SetFont('helvetica', 'B', 16); // Gras et plus gros
        $pdf->Cell(0, 10, 'LUDOVIC AUBAGUE', 0, 0); // À gauche
        $pdf->SetFont('helvetica', 'B', 14); // Retour à la taille normale
        $pdf->Cell(0, 10, 'FACTURE', 0, 1, 'R'); // À droite
        $pdf->Ln(10); // Saut de ligne de 10 points

        // Informations de contact
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'ludovic.aubague.plomberie@gmail.com', 0, 1, 'L');
        $pdf->Cell(0, 10, '898 route de Noaillat, 01 290 Cormoranche Sur Saône', 0, 1, 'L');
        $pdf->Cell(0, 10, 'N° SIRET: 891 940 751 000 11', 0, 1, 'L');
        $pdf->Cell(0, 10, 'RCDP:2021012714511665-17-F', 0, 1, 'L');
        $pdf->Ln(10); // Saut de ligne de 10 points
        $pdf->Ln(10); // Saut de ligne de 10 points

        // partie 2 de la facture
        // Définir la police en gras, taille 12
        $pdf->SetFont('helvetica', 'B', 12);

        // Cellule pour "Facturé à", alignée à gauche
        $pdf->Cell(100, 10, 'Facturé à', 0, 0, 'L');

        // Définir la police en normale, taille 12
        $pdf->SetFont('helvetica', '', 12);

        // Calculer la largeur de la cellule pour le numéro de facture
        $largeurFacture = $pdf->GetStringWidth('Facture n° ' . $numero);

        // Cellule pour le numéro de facture, alignée à droite
        $pdf->Cell(0, 10, 'Facture n° ' . $numero, 0, 1, 'R');

        // Génération du contenu PDF en tant que chaîne
        return $pdf->Output('', 'S');
    }

    private function savePdf(string $pdfContent): string
    {
        // Chemin où enregistrer le PDF
        $pdfPath = 'pdf/' . uniqid('pdf_') . '.pdf';

        // Enregistrement du PDF sur le serveur
        file_put_contents($pdfPath, $pdfContent);

        return $pdfPath;
    }
}
