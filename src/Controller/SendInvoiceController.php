<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SendInvoiceController extends AbstractController
{
    #[Route('/send/invoice', name: 'app_send_invoice')]
    public function sendInvoice(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $emailAddress = $request->request->get('email');
            $customText = $request->request->get('custom_text');
            $template = $request->request->get('template');
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $request->files->get('attachment');

            if (filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                $client = new Client();
                $apiKey = '1ce16fbb9259bc286d6bee9dba2ef72e';

                $attachments = [];
                if ($uploadedFile) {
                    $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $newFilename = $originalFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();

                    try {
                        $uploadedFile->move(
                            $this->getParameter('uploads_directory'),
                            $newFilename
                        );
                        $attachments[] = [
                            'filename' => $newFilename,
                            'content' => base64_encode(file_get_contents($this->getParameter('uploads_directory') . '/' . $newFilename)),
                            'type' => $uploadedFile->getClientMimeType(),
                        ];
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Erreur lors du téléchargement de la pièce jointe.');
                    }
                }

                $subject = '';
                $body = '';
                switch ($template) {
                    case 'template1':
                        $subject = 'Votre facture';
                        $body = "Bonjour,\n\nVoici votre facture.\n\n" . $customText . "\n\nCordialement,\nLudo Facture";
                        break;
                    case 'template2':
                        $subject = 'Facture disponible';
                        $body = "Bonjour,\n\nVous trouverez ci-joint votre facture.\n\n" . $customText . "\n\nCordialement,\nLudo Facture";
                        break;
                    case 'template3':
                        $subject = 'Détails de votre facture';
                        $body = "Bonjour,\n\nMerci de trouver votre facture en pièce jointe.\n\n" . $customText . "\n\nCordialement,\nLudo Facture";
                        break;
                }

                try {
                    $response = $client->post('https://bulk.api.mailtrap.io/api/send', [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $apiKey,
                            'Content-Type' => 'application/json',
                        ],
                        'json' => [
                            'from' => [
                                'email' => 'mailtrap@demomailtrap.com',
                                'name' => 'Mailtrap Test',
                            ],
                            'to' => [
                                ['email' => $emailAddress],
                            ],
                            'subject' => $subject,
                            'text' => $body,
                            'attachments' => $attachments,
                        ],
                    ]);

                    if ($response->getStatusCode() == 200) {
                        $this->addFlash('success', 'Email envoyé avec succès !');
                    } else {
                        $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email.');
                    }
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
                }

                return $this->redirectToRoute('app_send_invoice');
            } else {
                $this->addFlash('error', 'Adresse email invalide.');
            }
        }

        return $this->render('send_invoice/index.html.twig');
    }
}
