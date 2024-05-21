<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MAJFactureController extends AbstractController
{
    #[Route('/maj/facture', name: 'app_factures_index')]
    public function index(Request $request): Response
    {
        $factures = $this->loadAllFormData();

        $searchClient = $request->query->get('client');
        if ($searchClient) {
            $factures = array_filter($factures, function($facture) use ($searchClient) {
                return isset($facture['client']) && stripos($facture['client'], $searchClient) !== false;
            });
        }

        return $this->render('maj_facture/index.html.twig', [
            'factures' => $factures,
            'searchClient' => $searchClient,
        ]);
    }

    #[Route('/maj/facture/edit/{formId}', name: 'app_maj_facture_edit', methods: ['GET', 'POST'])]
    public function editFacture(Request $request, string $formId): Response
    {
        $formData = $this->loadFormData($formId);
        $formData['id'] = $formId;

        if ($request->isMethod('POST')) {
            $updatedFormData = $request->request->all();
            $this->saveFormData($formId, $updatedFormData);

            $pdfContent = $this->generatePdf($updatedFormData);
            $numero = $updatedFormData['numero'] ?? 'invoice';
            $pdfPath = $this->savePdf($pdfContent, $numero, true);

            return $this->redirectToRoute('app_confirmation', ['pdf_path' => $pdfPath]);
        }

        return $this->render('maj_facture/edit.html.twig', [
            'formData' => $formData,
            'formId' => $formId
        ]);
    }

    #[Route('/maj/facture/send/{formId}', name: 'app_maj_facture_send', methods: ['GET', 'POST'])]
    public function sendFactureEmail(Request $request, MailerInterface $mailer, string $formId): Response
    {
        $formData = $this->loadFormData($formId);
        $numero = $formData['numero'] ?? 'invoice';
        $pdfPath = $this->generatePdfPath($numero);

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->sendInvoiceByEmail($mailer, $formData, $pdfPath, $email);
                return $this->redirectToRoute('app_factures_index');
            } else {
                $this->addFlash('error', 'Adresse email invalide.');
            }
        }

        return $this->render('maj_facture/send.html.twig', [
            'formId' => $formId,
            'formData' => $formData,
        ]);
    }

    private function generatePdf(array $formData): string
    {
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();

        $numero = $formData['numero'] ?? '';
        $date = $formData['date'] ?? '';
        $client = $formData['client'] ?? '';
        $adresse1 = $formData['adresse1'] ?? '';
        $adresse2 = $formData['adresse2'] ?? '';
        $ville = $formData['ville'] ?? '';

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'LUDOVIC AUBAGUE', 0, 0); 
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'FACTURE', 0, 1, 'R'); 
        $pdf->Ln(20); 

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 10, 'ludovic.aubague.plomberie@gmail.com', 0, 1, 'L');
        $pdf->Cell(0, 10, '898 route de Noaillat, 01 290 Cormoranche Sur Saône', 0, 1, 'L');
        $pdf->Cell(0, 10, 'N° SIRET: 891 940 751 000 11', 0, 1, 'L');
        $pdf->Cell(0, 10, 'RCDP:2021012714511665-17-F', 0, 1, 'L');
        $pdf->Ln(20);

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

        $pdf->Cell(164, $cellHeight, 'TOTAL HT', 0, 0, 'L');
        $pdf->Cell(28, $cellHeight, sprintf("%.2f €", $totalHT), 1, 1, 'C');

        $tva = $totalHT * 0.0; 
        $pdf->Cell(164, $cellHeight, 'TVA 0%', 0, 0, 'L');
        $pdf->Cell(28, $cellHeight, sprintf("%.2f €", $tva), 1, 1, 'C');

        $totalTTC = $totalHT + $tva;
        $pdf->Cell(164, $cellHeight, 'TOTAL TTC', 0, 0, 'L');
        $pdf->Cell(28, $cellHeight, sprintf("%.2f €", $totalTTC), 1, 1, 'C');

        $remainingSpace = $pdf->getPageHeight() - $pdf->GetY() - $pdf->getMargins()['bottom'];
        $requiredSpace = 40; 

        if ($remainingSpace < $requiredSpace + 30) { 
            $pdf->AddPage();
        } else {
            $pdf->Ln(30); 
        }

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 10, 'Conditions et modalités de paiement', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 10, 'Chèque / Virement bancaire', 0, 1, 'L');
        $pdf->Cell(0, 10, 'IBAN: FR76 1027 8073 9300 0204 7830 188', 0, 1, 'L');
        $pdf->Cell(0, 10, 'BIC: CMCIFR2A TVA non applicable. Article 293B du CGI', 0, 1, 'L');

        return $pdf->Output('', 'S');
    }

    private function savePdf(string $pdfContent, string $invoiceNumber, bool $isModified = false): string
    {
        $directory = 'pdf';
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $suffix = $isModified ? '_Maj' : '';
        $pdfPath = $directory . '/Facture' . $invoiceNumber . $suffix . '.pdf';
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
                if (!isset($factureData['client'])) {
                    $factureData['client'] = ''; // Ajoute un champ client vide si manquant
                }
                $factureData['id'] = basename($filePath, '.json'); // Add the file name (without extension) as the ID
                $factures[] = $factureData;
            }
        }

        return $factures;
    }

    private function sendInvoiceByEmail(MailerInterface $mailer, array $formData, string $pdfPath, string $email): void
    {
        $email = (new Email())
            ->from('noreply@example.com')
            ->to($email)
            ->subject('Votre facture')
            ->text('Veuillez trouver ci-joint votre facture.')
            ->attachFromPath($pdfPath);

        $mailer->send($email);
    }

    private function generatePdfPath(string $invoiceNumber): string
    {
        $directory = 'pdf';
        $suffix = '';
        $pdfPath = $directory . '/Facture' . $invoiceNumber . $suffix . '.pdf';
        return $pdfPath;
    }
}
