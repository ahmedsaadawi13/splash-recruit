<?php
// FILE: /app/models/EmailTemplate.php

/**
 * EmailTemplate Model
 *
 * Manages email templates for notifications.
 */
class EmailTemplate extends Model
{
    protected $table = 'email_templates';
    protected $usesTenant = false; // Templates can be global or tenant-specific

    /**
     * Get template by code
     *
     * @param string $code
     * @param int|null $tenantId
     * @return array|null
     */
    public function getByCode($code, $tenantId = null)
    {
        // First try to find tenant-specific template
        if ($tenantId !== null) {
            $sql = "SELECT * FROM {$this->table}
                    WHERE code = :code AND tenant_id = :tenant_id
                    LIMIT 1";

            $this->db->query($sql);
            $this->db->bind(':code', $code);
            $this->db->bind(':tenant_id', $tenantId);
            $template = $this->db->fetch();

            if ($template) {
                return $template;
            }
        }

        // Fall back to default template
        $sql = "SELECT * FROM {$this->table}
                WHERE code = :code AND tenant_id IS NULL AND is_default = 1
                LIMIT 1";

        $this->db->query($sql);
        $this->db->bind(':code', $code);
        return $this->db->fetch();
    }

    /**
     * Get templates by tenant
     *
     * @param int|null $tenantId
     * @return array
     */
    public function getByTenant($tenantId = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";

        if ($tenantId === null) {
            $sql .= " AND tenant_id IS NULL";
        } else {
            $sql .= " AND (tenant_id = :tenant_id OR (tenant_id IS NULL AND is_default = 1))";
        }

        $sql .= " ORDER BY name ASC";

        $this->db->query($sql);

        if ($tenantId !== null) {
            $this->db->bind(':tenant_id', $tenantId);
        }

        return $this->db->fetchAll();
    }
}
