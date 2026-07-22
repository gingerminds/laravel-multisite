<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Services\Translation;

use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleServiceDrive;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Downloads the translation spreadsheet from Google Drive into a local
 * temporary xlsx file, using a per-site Google service account.
 *
 * Two kinds of source files are supported transparently:
 *  - a native Google Sheet (mime type application/vnd.google-apps.spreadsheet),
 *    which cannot be downloaded as-is and is exported to xlsx instead;
 *  - a real xlsx file uploaded to Drive, downloaded byte-for-byte.
 *
 * The caller is responsible for deleting the temporary file once it is no
 * longer needed (see TranslationService), so that the file content never
 * lingers on disk longer than the time it takes to parse it.
 */
class GoogleDriveTranslationClient
{
    private const string GOOGLE_SHEET_MIME_TYPE = 'application/vnd.google-apps.spreadsheet';
    private const string XLSX_MIME_TYPE         = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /**
     * @param array<string, mixed> $serviceAccountCredentials Decoded Google
     *   service account JSON (client_email, private_key, ...).
     *
     * @return string Absolute path to the downloaded temporary xlsx file.
     */
    public function downloadXlsx(string $fileId, array $serviceAccountCredentials): string
    {
        $client = new GoogleClient();
        $client->setAuthConfig($serviceAccountCredentials);
        $client->addScope(GoogleServiceDrive::DRIVE_READONLY);

        $drive = new GoogleServiceDrive($client);

        // `supportsAllDrives` lets a file living on a Shared Drive (Team
        // Drive) be reached too, not only files in the service account's own
        // "My Drive"; it is harmless for regular files.
        $mimeType = (string) $drive->files
            ->get($fileId, ['fields' => 'mimeType', 'supportsAllDrives' => true])
            ->getMimeType();

        if ($mimeType === self::GOOGLE_SHEET_MIME_TYPE) {
            // Native Google Sheet: it has no binary representation on Drive,
            // so it must be exported to xlsx on the fly.
            $response = $drive->files->export($fileId, self::XLSX_MIME_TYPE, ['alt' => 'media']);
        } else {
            // Regular xlsx (or any binary spreadsheet) uploaded to Drive:
            // download the raw bytes.
            $response = $drive->files->get($fileId, ['alt' => 'media', 'supportsAllDrives' => true]);
        }

        /** @var ResponseInterface $response */
        $tempPath = tempnam(sys_get_temp_dir(), 'gm_translation_');
        if ($tempPath === false) {
            throw new RuntimeException('Unable to create a temporary file to download the translation xlsx.');
        }

        // PhpSpreadsheet's xlsx reader relies on the file extension /
        // ZipArchive, so the temp file needs an explicit .xlsx name.
        $xlsxPath = $tempPath . '.xlsx';
        rename($tempPath, $xlsxPath);

        file_put_contents($xlsxPath, $response->getBody()->getContents());

        return $xlsxPath;
    }
}
