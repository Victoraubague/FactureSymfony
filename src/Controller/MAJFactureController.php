<?php
// src/Controller/MAJFactureController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use TCPDF;

class MAJFactureController extends AbstractController
{
    #[Route('/maj/facture', name: 'app_factures_index')]
    public function index(): Response
    {
        // Lire toutes les factures depuis les fichiers JSON
        $factures = $this->loadAllFormData();

        return $this->render('maj_facture/index.html.twig', [
            'factures' => $factures,
        ]);
    }

    #[Route('/maj/facture/edit/{formId}', name: 'app_maj_facture_edit', methods: ['GET', 'POST'])]
    public function editFacture(Request $request, string $formId): Response
    {
        $formData = $this->loadFormData($formId);
        $formData['id'] = $formId; // Ajoutez l'ID au tableau de données

        if ($request->isMethod('POST')) {
            $updatedFormData = $request->request->all();
            $this->saveFormData($formId, $updatedFormData);

            $pdfContent = $this->generatePdf($updatedFormData);
            $pdfPath = $this->savePdf($pdfContent, true);  // Pass true to indicate modification

            return $this->redirectToRoute('app_confirmation', ['pdf_path' => $pdfPath]);
        }

        return $this->render('maj_facture/edit.html.twig', [
            'formData' => $formData,
            'formId' => $formId
        ]);
    }

    private function generatePdf(array $formData): string
    {
        // Create new TCPDF instance
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();

        // Extracting variables from the formData array
        $numero = $formData['numero'] ?? '';
        $date = $formData['date'] ?? '';
        $adresse1 = $formData['adresse1'] ?? '';
        $adresse2 = $formData['adresse2'] ?? '';
        $ville = $formData['ville'] ?? '';

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
            $quantite = (int) ($formData['quantite' . ($i == 0 ? '' : $i)] ?? 0);
            $designation = $formData['designation' . ($i == 0 ? '' : $i)] ?? '';
            $prixUnitaire = (float) ($formData['prixUnitaire' . ($i == 0 ? '' : $i)] ?? 0);

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

    private function savePdf(string $pdfContent, bool $isModified = false): string
    {
        $directory = 'pdf';
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $suffix = $isModified ? '_Maj' : '';
        $pdfPath = $directory . '/' . uniqid('pdf_') . $suffix . '.pdf';
        file_put_contents($pdfPath, $pdfContent);
        return $pdfPath;
    }

    private function saveFormData(string $formId, array $formData): string
    {
        $directory = 'forms';
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $formPath = $directory . '/' . $formId . '.json';
        file_put_contents($formPath, json_encode($formData));
        return $formPath;
    }

    private function loadFormData(string $formId): array
    {
        $formPath = 'forms/' . $formId . '.json';
        if (file_exists($formPath)) {
            return json_decode(file_get_contents($formPath), true);
        }

        return [];
    }

    private function loadAllFormData(): array
    {
        $directory = 'forms';
        $factures = [];

        if (is_dir($directory)) {
            foreach (glob($directory . '/*.json') as $filePath) {
                $factureData = json_decode(file_get_contents($filePath), true);
                $factureData['id'] = basename($filePath, '.json'); // Add the file name (without extension) as the ID
                $factures[] = $factureData;
            }
        }

        return $factures;
    }
}
