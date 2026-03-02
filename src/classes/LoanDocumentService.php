<?php

namespace App;

use PDO;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Exception;

class LoanDocumentService
{
    private $db;
    private $filesystem;

    public function __construct(PDO $db)
    {
        $this->db = $db;

        // Point Flysystem to the secure storage folder we created earlier
        // __DIR__ is src/classes/, so we go up two levels to reach the root storage folder
        $adapter = new LocalFilesystemAdapter(__DIR__ . '/../../storage/kptn_receipts');
        $this->filesystem = new Filesystem($adapter);
    }

    /**
     * Handles the upload, validation, and database saving of a KPTN receipt.
     * * @param int $loanId The ID of the loan this receipt belongs to
     * @param int|null $uploadedBy The employe_id of the staff member uploading it
     * @param array $fileArray The $_FILES array from the form submission (e.g., $_FILES['kptn_receipt'])
     * @param string|null $description Optional note about the document
     * @return bool True on success
     * @throws Exception If upload or validation fails
     */
    public function uploadKptnReceipt($loanId, $uploadedBy, $fileArray, $description = null)
    {
        // 1. Basic Validation
        if (!isset($fileArray['tmp_name']) || empty($fileArray['tmp_name'])) {
            throw new Exception("No file was uploaded.");
        }

        if ($fileArray['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error code: " . $fileArray['error']);
        }

        // 2. Validate File Type (Security measure)
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        
        // Use mime_content_type to get the actual file type, not just what the browser claims
        $actualMimeType = mime_content_type($fileArray['tmp_name']);
        
        if (!in_array($actualMimeType, $allowedMimeTypes)) {
            throw new Exception("Invalid file type. Only JPG, PNG, and PDF are allowed.");
        }

        // 3. Generate the dynamic Year/Month folder structure and a unique filename
        $year = date('Y');
        $month = date('m');
        
        // Example: "loan_884_1678901234.pdf" (Prevents overwriting files with the same name)
        $extension = pathinfo($fileArray['name'], PATHINFO_EXTENSION);
        $uniqueFilename = "loan_{$loanId}_" . time() . "." . $extension;
        
        // Final path inside the storage folder: "2026/03/loan_884_1678901234.pdf"
        $savePath = "{$year}/{$month}/{$uniqueFilename}";

        // 4. Move the file using Flysystem
        try {
            // Read the temporary file content as a stream (better for memory)
            $fileStream = fopen($fileArray['tmp_name'], 'r+');
            
            // Flysystem automatically creates the YYYY/MM folders if they don't exist
            $this->filesystem->writeStream($savePath, $fileStream);
            
            if (is_resource($fileStream)) {
                fclose($fileStream);
            }
        } catch (\League\Flysystem\FilesystemException $e) {
            throw new Exception("Failed to save file to disk: " . $e->getMessage());
        }

        // 5. Save the record to the database
        $fileSizeKb = round($fileArray['size'] / 1024); // Convert bytes to KB

        $sql = "INSERT INTO Loan_Documents 
                (loan_id, uploaded_by, file_path, file_name, mime_type, file_size_kb, description) 
                VALUES 
                (:loan_id, :uploaded_by, :file_path, :file_name, :mime_type, :file_size_kb, :description)";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':loan_id'      => $loanId,
            ':uploaded_by'  => $uploadedBy,
            ':file_path'    => $savePath,
            ':file_name'    => $fileArray['name'], // Original filename for reference
            ':mime_type'    => $actualMimeType,
            ':file_size_kb' => $fileSizeKb,
            ':description'  => $description
        ]);

        if (!$result) {
            // If DB insert fails, delete the file from disk to prevent orphans
            $this->filesystem->delete($savePath);
            throw new Exception("Failed to save document record to the database.");
        }

        return true;
    }
}