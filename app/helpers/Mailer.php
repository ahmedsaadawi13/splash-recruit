<?php
// FILE: /app/helpers/Mailer.php

/**
 * Mailer helper class
 *
 * Handles email sending (simulated - logs to database and file).
 * Can be extended to use real SMTP service.
 */
class Mailer
{
    private $to;
    private $subject;
    private $body;
    private $from;
    private $fromName;
    private $tenantId;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->from = getenv('MAIL_FROM_ADDRESS') ?: 'noreply@splashrecruit.com';
        $this->fromName = getenv('MAIL_FROM_NAME') ?: 'SplashRecruit';
    }

    /**
     * Set recipient
     *
     * @param string $to
     * @return self
     */
    public function to($to)
    {
        $this->to = $to;
        return $this;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return self
     */
    public function subject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set body
     *
     * @param string $body
     * @return self
     */
    public function body($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Set from address
     *
     * @param string $from
     * @param string|null $name
     * @return self
     */
    public function from($from, $name = null)
    {
        $this->from = $from;
        if ($name !== null) {
            $this->fromName = $name;
        }
        return $this;
    }

    /**
     * Set tenant ID
     *
     * @param int $tenantId
     * @return self
     */
    public function setTenantId($tenantId)
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    /**
     * Send email
     *
     * @return bool
     */
    public function send()
    {
        // Validate required fields
        if (!$this->to || !$this->subject || !$this->body) {
            error_log('Mailer Error: Missing required fields (to, subject, or body)');
            return false;
        }

        // Save to notifications table
        $db = Database::getInstance();

        try {
            $sql = "INSERT INTO notifications (tenant_id, recipient_email, subject, body, type, status, created_at)
                    VALUES (:tenant_id, :recipient_email, :subject, :body, :type, :status, NOW())";

            $db->query($sql);
            $db->bind(':tenant_id', $this->tenantId);
            $db->bind(':recipient_email', $this->to);
            $db->bind(':subject', $this->subject);
            $db->bind(':body', $this->body);
            $db->bind(':type', 'candidate_email');
            $db->bind(':status', 'sent'); // Simulated as sent immediately

            $db->execute();

            // Log to file
            $this->logToFile();

            return true;
        } catch (Exception $e) {
            error_log('Mailer Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log email to file
     *
     * @return void
     */
    private function logToFile()
    {
        $logPath = '/home/user/SplashRecruit/storage/logs/emails.log';
        $logEntry = sprintf(
            "[%s] To: %s | Subject: %s | Body: %s\n",
            date('Y-m-d H:i:s'),
            $this->to,
            $this->subject,
            substr($this->body, 0, 100) . '...'
        );

        file_put_contents($logPath, $logEntry, FILE_APPEND);
    }

    /**
     * Send email using template
     *
     * @param int $templateId
     * @param array $placeholders
     * @return bool
     */
    public function sendTemplate($templateId, $placeholders = [])
    {
        $db = Database::getInstance();

        $sql = "SELECT * FROM email_templates WHERE id = :id LIMIT 1";
        $db->query($sql);
        $db->bind(':id', $templateId);
        $template = $db->fetch();

        if (!$template) {
            error_log('Mailer Error: Template not found - ID: ' . $templateId);
            return false;
        }

        // Replace placeholders
        $subject = $this->replacePlaceholders($template['subject'], $placeholders);
        $body = $this->replacePlaceholders($template['body'], $placeholders);

        $this->subject($subject);
        $this->body($body);

        if (isset($placeholders['tenant_id'])) {
            $this->setTenantId($placeholders['tenant_id']);
        }

        return $this->send();
    }

    /**
     * Replace placeholders in template
     *
     * @param string $text
     * @param array $placeholders
     * @return string
     */
    private function replacePlaceholders($text, $placeholders)
    {
        foreach ($placeholders as $key => $value) {
            $text = str_replace('{{' . $key . '}}', $value, $text);
        }

        return $text;
    }

    /**
     * Static method to quickly send email
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param int|null $tenantId
     * @return bool
     */
    public static function quickSend($to, $subject, $body, $tenantId = null)
    {
        $mailer = new self();
        $mailer->to($to)
               ->subject($subject)
               ->body($body);

        if ($tenantId !== null) {
            $mailer->setTenantId($tenantId);
        }

        return $mailer->send();
    }
}
