<?php
// FILE: /app/models/Tag.php

/**
 * Tag Model
 *
 * Manages tags for candidates.
 */
class Tag extends Model
{
    protected $table = 'tags';

    /**
     * Get all tags for tenant
     *
     * @param int $tenantId
     * @return array
     */
    public function getByTenant($tenantId)
    {
        $sql = "SELECT t.*,
                (SELECT COUNT(*) FROM candidate_tags WHERE tag_id = t.id) as usage_count
                FROM {$this->table} t
                WHERE t.tenant_id = :tenant_id
                ORDER BY t.name ASC";

        $this->db->query($sql);
        $this->db->bind(':tenant_id', $tenantId);
        return $this->db->fetchAll();
    }

    /**
     * Find or create tag by name
     *
     * @param string $name
     * @param int $tenantId
     * @return int Tag ID
     */
    public function findOrCreate($name, $tenantId)
    {
        // Try to find existing tag
        $sql = "SELECT id FROM {$this->table}
                WHERE name = :name AND tenant_id = :tenant_id
                LIMIT 1";

        $this->db->query($sql);
        $this->db->bind(':name', $name);
        $this->db->bind(':tenant_id', $tenantId);
        $tag = $this->db->fetch();

        if ($tag) {
            return $tag['id'];
        }

        // Create new tag
        return $this->insert([
            'tenant_id' => $tenantId,
            'name' => $name
        ]);
    }
}
