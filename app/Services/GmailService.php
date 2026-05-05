<?php

namespace App\Services;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Illuminate\Support\Facades\Log;

/**
 * Envoi d'emails via Gmail Workspace + Service Account avec Domain-Wide Delegation.
 *
 * Avantages :
 *  - Aucune mot de passe à gérer (pas de SMTP par agence)
 *  - "From: bordeaux@salti.fr" envoyé directement par Gmail
 *  - Authentification SPF/DKIM auto (pas de risque de spam)
 *
 * Pré-requis (à faire UNE FOIS par le QSE) :
 *  1. Activer Gmail API dans GCP
 *  2. Créer un Service Account, télécharger le JSON
 *  3. Domain-Wide Delegation dans Workspace Admin
 *     (scope : https://www.googleapis.com/auth/gmail.send)
 *  4. Placer le JSON dans storage/app/private/gmail-service-account.json
 *  5. Définir GMAIL_SERVICE_ACCOUNT_PATH dans .env
 *
 * Voir doc/HOW_TO_SETUP_GMAIL.md pour le pas-à-pas détaillé.
 */
class GmailService
{
    /**
     * Envoie un email "as" l'agence indiquée.
     *
     * @param string $fromEmail   ex. bordeaux@salti.fr (l'agence qui envoie)
     * @param string $fromName    ex. "Agence Bordeaux"
     * @param string $to          destinataire
     * @param string $subject
     * @param string $htmlBody
     * @param array<int, array{path: string, name: string}> $attachments  Pièces jointes (chemins absolus)
     * @return bool
     */
    public function sendAs(
        string $fromEmail,
        string $fromName,
        string $to,
        string $subject,
        string $htmlBody,
        array $attachments = [],
    ): bool {
        try {
            $client = $this->buildClient($fromEmail);
            $gmail = new Gmail($client);

            $rawEmail = $this->buildRawEmail($fromEmail, $fromName, $to, $subject, $htmlBody, $attachments);
            $message = new Message();
            $message->setRaw(rtrim(strtr(base64_encode($rawEmail), '+/', '-_'), '='));

            $gmail->users_messages->send('me', $message);
            return true;
        } catch (\Throwable $e) {
            Log::error('[GmailService] Échec envoi : '.$e->getMessage(), [
                'from' => $fromEmail,
                'to' => $to,
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * Vérifie que la config Gmail est présente.
     */
    public function isConfigured(): bool
    {
        $path = $this->serviceAccountPath();
        return $path && file_exists($path);
    }

    private function buildClient(string $impersonateEmail): Client
    {
        $client = new Client();
        $client->setAuthConfig($this->serviceAccountPath());
        $client->setSubject($impersonateEmail); // ← magie de la Domain-Wide Delegation
        $client->setScopes([Gmail::GMAIL_SEND]);
        return $client;
    }

    private function serviceAccountPath(): ?string
    {
        $path = config('services.gmail.service_account_path')
            ?? env('GMAIL_SERVICE_ACCOUNT_PATH', storage_path('app/private/gmail-service-account.json'));
        return $path;
    }

    /**
     * Construit un email RFC 2822 multipart (HTML + pièces jointes).
     */
    private function buildRawEmail(
        string $fromEmail,
        string $fromName,
        string $to,
        string $subject,
        string $htmlBody,
        array $attachments,
    ): string {
        $boundary = 'pdp_'.uniqid();
        $encodedSubject = '=?UTF-8?B?'.base64_encode($subject).'?=';

        $headers = [
            'From: '.$this->encodeRfc2822($fromName).' <'.$fromEmail.'>',
            'To: '.$to,
            'Subject: '.$encodedSubject,
            'MIME-Version: 1.0',
            'Content-Type: multipart/mixed; boundary="'.$boundary.'"',
        ];

        $parts = [];

        // Corps HTML
        $parts[] = "--$boundary\r\n"
            ."Content-Type: text/html; charset=UTF-8\r\n"
            ."Content-Transfer-Encoding: base64\r\n\r\n"
            .chunk_split(base64_encode($htmlBody));

        // Pièces jointes
        foreach ($attachments as $att) {
            if (! file_exists($att['path'])) continue;
            $filename = $att['name'] ?? basename($att['path']);
            $mime = mime_content_type($att['path']) ?: 'application/octet-stream';
            $parts[] = "--$boundary\r\n"
                ."Content-Type: {$mime}; name=\"".$this->encodeRfc2822($filename)."\"\r\n"
                ."Content-Transfer-Encoding: base64\r\n"
                ."Content-Disposition: attachment; filename=\"".$this->encodeRfc2822($filename)."\"\r\n\r\n"
                .chunk_split(base64_encode(file_get_contents($att['path'])));
        }

        return implode("\r\n", $headers)."\r\n\r\n".implode("\r\n", $parts)."\r\n--$boundary--";
    }

    private function encodeRfc2822(string $str): string
    {
        if (preg_match('/[^\x20-\x7e]/', $str)) {
            return '=?UTF-8?B?'.base64_encode($str).'?=';
        }
        return $str;
    }
}
