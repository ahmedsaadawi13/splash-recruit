<?php
// FILE: /app/helpers/FileUpload.php

/**
 * FileUpload helper class
 *
 * Handles file uploads with validation and security.
 */
class FileUpload
{
    private $file;
    private $allowedTypes = [];
    private $maxSize = 5242880; // 5MB default
    private $uploadPath = '/home/user/SplashRecruit/storage/uploads/';
    private $error = null;

    /**
     * Constructor
     *
     * @param array $file $_FILES array element
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * Set allowed file types
     *
     * @param array $types
     * @return self
     */
    public function setAllowedTypes($types)
    {
        $this->allowedTypes = $types;
        return $this;
    }

    /**
     * Set maximum file size
     *
     * @param int $size Size in bytes
     * @return self
     */
    public function setMaxSize($size)
    {
        $this->maxSize = $size;
        return $this;
    }

    /**
     * Set upload path
     *
     * @param string $path
     * @return self
     */
    public function setUploadPath($path)
    {
        $this->uploadPath = rtrim($path, '/') . '/';
        return $this;
    }

    /**
     * Validate uploaded file
     *
     * @return bool
     */
    public function validate()
    {
        // Check if file was uploaded
        if (!isset($this->file['tmp_name']) || !is_uploaded_file($this->file['tmp_name'])) {
            $this->error = 'No file uploaded.';
            return false;
        }

        // Check for upload errors
        if ($this->file['error'] !== UPLOAD_ERR_OK) {
            $this->error = $this->getUploadErrorMessage($this->file['error']);
            return false;
        }

        // Check file size
        if ($this->file['size'] > $this->maxSize) {
            $this->error = 'File size exceeds maximum allowed size of ' . $this->formatBytes($this->maxSize) . '.';
            return false;
        }

        // Check file type
        if (!empty($this->allowedTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $this->file['tmp_name']);
            finfo_close($finfo);

            $extension = strtolower(pathinfo($this->file['name'], PATHINFO_EXTENSION));

            $isValidMime = in_array($mimeType, $this->allowedTypes);
            $isValidExtension = in_array($extension, $this->allowedTypes);

            if (!$isValidMime && !$isValidExtension) {
                $this->error = 'File type not allowed.';
                return false;
            }
        }

        return true;
    }

    /**
     * Upload file
     *
     * @param string|null $customName
     * @return array|false Returns array with file info or false on failure
     */
    public function upload($customName = null)
    {
        if (!$this->validate()) {
            return false;
        }

        // Generate unique filename
        $extension = strtolower(pathinfo($this->file['name'], PATHINFO_EXTENSION));
        $filename = $customName ?? uniqid() . '_' . bin2hex(random_bytes(8));
        $filename .= '.' . $extension;

        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }

        $destination = $this->uploadPath . $filename;

        // Move uploaded file
        if (move_uploaded_file($this->file['tmp_name'], $destination)) {
            // Get MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $destination);
            finfo_close($finfo);

            return [
                'original_name' => $this->file['name'],
                'stored_name' => $filename,
                'file_path' => $destination,
                'relative_path' => str_replace('/home/user/SplashRecruit/', '', $destination),
                'mime_type' => $mimeType,
                'size' => $this->file['size'],
                'extension' => $extension
            ];
        }

        $this->error = 'Failed to move uploaded file.';
        return false;
    }

    /**
     * Get upload error
     *
     * @return string|null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Get upload error message
     *
     * @param int $code
     * @return string
     */
    private function getUploadErrorMessage($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'File size exceeds maximum allowed size.';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension.';
            default:
                return 'Unknown upload error.';
        }
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Delete file
     *
     * @param string $filePath
     * @return bool
     */
    public static function delete($filePath)
    {
        if (file_exists($filePath) && is_file($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    /**
     * Get file MIME type
     *
     * @param string $filePath
     * @return string
     */
    public static function getMimeType($filePath)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return $mimeType;
    }
}
