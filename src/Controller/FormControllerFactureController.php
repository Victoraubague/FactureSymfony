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
        $date = $request->request->get('date');
        $adresse1 = $request->request->get('adresse1');
        $adresse2 = $request->request->get('adresse2');
        $ville = $request->request->get('ville');
        $quantite = $request->request->get('quantite');
        $designation = $request->request->get('designation');
        $prixUnitaire = $request->request->get('prixUnitaire');
        $montant = $request->request->get('montant');
    
        // Si le formulaire a été soumis
        if ($numero !== null && $date !== null && $adresse1 !== null && $adresse2 !== null && $ville !== null) {
            // Générer le PDF avec les données soumises
            $pdfContent = $this->generatePdf($numero, $date, $adresse1, $adresse2, $ville, $quantite, $designation, $prixUnitaire, $montant);
    
            // Enregistrer le PDF sur le serveur
            $pdfPath = $this->savePdf($pdfContent);
    
            // Rediriger vers la page de confirmation avec le chemin du PDF
            return $this->redirectToRoute('app_confirmation', ['pdf_path' => $pdfPath]);
        }
    
        // Afficher le formulaire
        return $this->render('form_controller_facture/index.html.twig');
    }
    

    private function generatePdf(string $numero, string $date, string $adresse1, string $adresse2, string $ville, string $quantite, string $designation, string $prixUnitaire, string $montant): string
    {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();
    
        // Header Information
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'LUDOVIC AUBAGUE', 0, 0); // Left aligned
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'FACTURE', 0, 1, 'R'); // Right aligned
        $pdf->Ln(10);
    
        // Contact Information
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'ludovic.aubague.plomberie@gmail.com', 0, 1, 'L');
        $pdf->Cell(0, 10, '898 route de Noaillat, 01 290 Cormoranche Sur Saône', 0, 1, 'L');
        $pdf->Cell(0, 10, 'N° SIRET: 891 940 751 000 11', 0, 1, 'L');
        $pdf->Cell(0, 10, 'RCDP:2021012714511665-17-F', 0, 1, 'L');
        $pdf->Ln(20);
    
        // Billing Information
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(100, 10, 'Facturé à', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Facture n° ' . $numero, 0, 1, 'R');
        $pdf->Cell(0, 10, $adresse1, 0, 0, 'L');
        $pdf->Cell(0, 10, 'Date ' . $date, 0, 1, 'R');
        $pdf->Cell(0, 10, $adresse2, 0, 0, 'L');
        $pdf->Ln();
        $pdf->Cell(0, 10, $ville, 0, 0, 'L');
    
// Table Setup
$pdf->SetXY(10, 150);
$cellHeight = 8;
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(20, $cellHeight, 'Qté', 1, 0, 'C');
$pdf->Cell(120, $cellHeight, 'Désignation', 1, 0, 'C');
$pdf->Cell(24, $cellHeight, 'Prix unit.', 1, 0, 'C');
$pdf->Cell(28, $cellHeight, 'Montant', 1, 1, 'C');

// Table Data
$pdf->SetFont('helvetica', '', 12);
$description = $quantite . ' - ' . $designation;
$descriptionLines = $pdf->getNumLines($description, 120); // Nombre de lignes du texte dans la cellule
$multiCellHeight = $cellHeight * $descriptionLines;

$pdf->Cell(20, $multiCellHeight, $quantite, 1, 0, 'C'); // Utilisez la hauteur de multiCell ici aussi
$pdf->MultiCell(120, $multiCellHeight, $description, 1, 'L', false, 0);
$pdf->Cell(24, $multiCellHeight, $prixUnitaire . ' €', 1, 0, 'C'); // Utilisez la hauteur de multiCell ici aussi
$pdf->Cell(28, $multiCellHeight, $montant . ' €', 1, 1, 'C'); // Utilisez la hauteur de multiCell ici aussi

// Additional "cc" and "aa" cells
$xPositionForCC = 130; // Position for "cc" under "Prix unit."
$xPositionForAA = 174; // Position for "aa" under "Montant"
for ($i = 0; $i < 3; $i++) {
    $pdf->SetX($xPositionForCC);
    $pdf->Cell(24, $cellHeight, 'cc', 0, 0); // "cc" text without border
    $pdf->SetX($xPositionForAA);
    $pdf->Cell(28, $cellHeight, 'aa', 1, 1, 'C'); // "aa" cell with border
}

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
