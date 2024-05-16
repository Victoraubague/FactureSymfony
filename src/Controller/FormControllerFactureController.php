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
        // Check if the form has been submitted
        if ($request->isMethod('POST')) {
            // Generate the PDF with the submitted data
            $pdfContent = $this->generatePdf($request);

            // Save the PDF on the server
            $pdfPath = $this->savePdf($pdfContent);

            // Redirect to the confirmation page with the path to the PDF
            return $this->redirectToRoute('app_confirmation', ['pdf_path' => $pdfPath]);
        }

        // Display the form
        return $this->render('form_controller_facture/index.html.twig');
    }
    private function generatePdf(Request $request): string {
        // Create new TCPDF instance
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();
    
        // Extracting variables from the request
        $numero = $request->request->get('numero');
        $date = $request->request->get('date');
        $adresse1 = $request->request->get('adresse1');
        $adresse2 = $request->request->get('adresse2');
        $ville = $request->request->get('ville');
    
        // Header setup
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'LUDOVIC AUBAGUE', 0, 0); // Left align
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'FACTURE', 0, 1, 'R'); // Right align
        $pdf->Ln(20); // Space for header separation
    
        // Contact information
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 10, 'ludovic.aubague.plomberie@gmail.com', 0, 1, 'L');
        $pdf->Cell(0, 10, '898 route de Noaillat, 01 290 Cormoranche Sur Saône', 0, 1, 'L');
        $pdf->Cell(0, 10, 'N° SIRET: 891 940 751 000 11', 0, 1, 'L');
        $pdf->Cell(0, 10, 'RCDP:2021012714511665-17-F', 0, 1, 'L');
        $pdf->Ln(20);
    
        // Billing part
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(100, 10, 'Facturé à', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 10, 'Facture n° ' . $numero, 0, 1, 'R');
        $pdf->Cell(0, 10, $adresse1, 0, 0, 'L');
        $pdf->Cell(0, 10, 'Date ' . $date, 0, 1, 'R');
        $pdf->Cell(0, 10, $adresse2, 0, 0, 'L');
        $pdf->Ln();
        $pdf->Cell(0, 10, $ville, 0, 0, 'L');
        $pdf->Ln(20);
    
        // Table setup
        $pdf->SetXY(10, 150);
        $cellHeight = 8;
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(20, $cellHeight, 'Qté', 1, 0, 'C');
        $pdf->Cell(120, $cellHeight, 'Désignation', 1, 0, 'C');
        $pdf->Cell(24, $cellHeight, 'Prix unit.', 1, 0, 'C');
        $pdf->Cell(28, $cellHeight, 'Montant', 1, 1, 'C');
    
        $totalHT = 0;
        $pdf->SetFont('helvetica', '', 10);
        for ($i = 0; $i <= 7; $i++) {
            $quantite = (int) $request->request->get('quantite' . ($i == 0 ? '' : $i));
            $designation = $request->request->get('designation' . ($i == 0 ? '' : $i));
            $prixUnitaire = (float) $request->request->get('prixUnitaire' . ($i == 0 ? '' : $i));
    
            if (!empty($quantite) && !empty($designation) && !empty($prixUnitaire)) {
                $montant = $quantite * $prixUnitaire;
                $description = $designation;
                $multiCellHeight = $pdf->getStringHeight(120, $description);
    
                $pdf->Cell(20, $multiCellHeight, $quantite, 1, 0, 'C');
                $pdf->MultiCell(120, $multiCellHeight, $description, 1, 'L', false, 0);
                $pdf->Cell(24, $multiCellHeight, sprintf("%.2f €", $prixUnitaire), 1, 0, 'C');
                $pdf->Cell(28, $multiCellHeight, sprintf("%.2f €", $montant), 1, 1, 'C');
    
                $totalHT += $montant;
            }
        }
    
        // Summarize totals and tax
        $pdf->Cell(164, $cellHeight, 'TOTAL HT', 0, 0, 'L');
        $pdf->Cell(28, $cellHeight, sprintf("%.2f €", $totalHT), 1, 1, 'C');
    
        $tva = $totalHT * 0.0; // Adjust if tax rate changes
        $pdf->Cell(164, $cellHeight, 'TVA 0%', 0, 0, 'L');
        $pdf->Cell(28, $cellHeight, sprintf("%.2f €", $tva), 1, 1, 'C');
    
        $totalTTC = $totalHT + $tva;
        $pdf->Cell(164, $cellHeight, 'TOTAL TTC', 0, 0, 'L');
        $pdf->Cell(28, $cellHeight, sprintf("%.2f €", $totalTTC), 1, 1, 'C');
    
        // Check if there is enough space for the conditions at the bottom of the page
        $remainingSpace = $pdf->getPageHeight() - $pdf->GetY() - $pdf->getMargins()['bottom'];
        $requiredSpace = 40; // Approximate height needed for the payment conditions section
    
        if ($remainingSpace < $requiredSpace + 30) { // Add a 30mm buffer
            $pdf->AddPage();
        } else {
            $pdf->Ln(30); // Ensure 30mm distance from the table above
        }
    
        // Payment conditions at the bottom left
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 10, 'Conditions et modalités de paiement', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 10, 'Chèque / Virement bancaire', 0, 1, 'L');
        $pdf->Cell(0, 10, 'IBAN: FR76 1027 8073 9300 0204 7830 188', 0, 1, 'L');
        $pdf->Cell(0, 10, 'BIC: CMCIFR2A TVA non applicable. Article 293B du CGI', 0, 1, 'L');
    
        return $pdf->Output('', 'S');
    }
    
    
    

    private function savePdf(string $pdfContent): string
    {
        $pdfPath = 'pdf/' . uniqid('pdf_') . '.pdf';
        file_put_contents($pdfPath, $pdfContent);
        return $pdfPath;
    }
}
