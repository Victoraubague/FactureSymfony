<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConfirmationController extends AbstractController
{
    #[Route('/confirmation', name: 'app_confirmation')]
    public function index(): Response
    {
        // Path to the PDF directory
        $pdfDir = $this->getParameter('kernel.project_dir') . '/public/pdf';

        // Get all PDF files from the directory
        $pdfFiles = glob($pdfDir . '/*.pdf');

        // Extract just the filenames
        $pdfFilenames = array_map('basename', $pdfFiles);

        return $this->render('confirmation/index.html.twig', [
            'pdfFiles' => $pdfFilenames,
        ]);
    }
}
