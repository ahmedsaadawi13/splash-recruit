<?php
// FILE: /app/core/Model.php

/**
 * Base Model class
 *
 * Provides common database operations for all models.
 * Supports multi-tenant data isolation.
 */
class Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $usesTenant = true; // Most tables are tenant-scoped
    protected $timestamps = true; // Auto-manage created_at and updated_at

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find record by ID
     *
     * @param int $id
     * @param int|null $tenantId
     * @return array|null
     */
    public function find($id, $tenantId = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";

        if ($this->usesTenant && $tenantId !== null) {
            $sql .= " AND tenant_id = :tenant_id";
        }

        $sql .= " LIMIT 1";

        $this->db->query($sql);
        $this->db->bind(':id', $id);

        if ($this->usesTenant && $tenantId !== null) {
            $this->db->bind(':tenant_id', $tenantId);
        }

        return $this->db->fetch();
    }

    /**
     * Find all records
     *
     * @param int|null $tenantId
     * @param array $conditions
     * @param string $orderBy
     * @param int|null $limit
     * @param int $offset
     * @return array
     */
    public function findAll($tenantId = null, $conditions = [], $orderBy = null, $limit = null, $offset = 0)
    {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";

        if ($this->usesTenant && $tenantId !== null) {
            $sql .= " AND tenant_id = :tenant_id";
        }

        foreach ($conditions as $field => $value) {
            $sql .= " AND {$field} = :{$field}";
        }

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        $this->db->query($sql);

        if ($this->usesTenant && $tenantId !== null) {
            $this->db->bind(':tenant_id', $tenantId);
        }

        foreach ($conditions as $field => $value) {
            $this->db->bind(":{$field}", $value);
        }

        return $this->db->fetchAll();
    }

    /**
     * Insert a new record
     *
     * @param array $data
     * @return int Last insert ID
     */
    public function insert($data)
    {
        if ($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $fields = array_keys($data);
        $values = array_values($data);

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ")
                VALUES (:" . implode(', :', $fields) . ")";

        $this->db->query($sql);

        foreach ($data as $field => $value) {
            $this->db->bind(":{$field}", $value);
        }

        $this->db->execute();

        return $this->db->lastInsertId();
    }

    /**
     * Update a record
     *
     * @param int $id
     * @param array $data
     * @param int|null $tenantId
     * @return bool
     */
    public function update($id, $data, $tenantId = null)
    {
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $fields = [];
        foreach (array_keys($data) as $field) {
            $fields[] = "{$field} = :{$field}";
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) .
               " WHERE {$this->primaryKey} = :id";

        if ($this->usesTenant && $tenantId !== null) {
            $sql .= " AND tenant_id = :tenant_id";
        }

        $this->db->query($sql);

        foreach ($data as $field => $value) {
            $this->db->bind(":{$field}", $value);
        }

        $this->db->bind(':id', $id);

        if ($this->usesTenant && $tenantId !== null) {
            $this->db->bind(':tenant_id', $tenantId);
        }

        return $this->db->execute();
    }

    /**
     * Delete a record
     *
     * @param int $id
     * @param int|null $tenantId
     * @return bool
     */
    public function delete($id, $tenantId = null)
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";

        if ($this->usesTenant && $tenantId !== null) {
            $sql .= " AND tenant_id = :tenant_id";
        }

        $this->db->query($sql);
        $this->db->bind(':id', $id);

        if ($this->usesTenant && $tenantId !== null) {
            $this->db->bind(':tenant_id', $tenantId);
        }

        return $this->db->execute();
    }

    /**
     * Count records
     *
     * @param int|null $tenantId
     * @param array $conditions
     * @return int
     */
    public function count($tenantId = null, $conditions = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE 1=1";

        if ($this->usesTenant && $tenantId !== null) {
            $sql .= " AND tenant_id = :tenant_id";
        }

        foreach ($conditions as $field => $value) {
            $sql .= " AND {$field} = :{$field}";
        }

        $this->db->query($sql);

        if ($this->usesTenant && $tenantId !== null) {
            $this->db->bind(':tenant_id', $tenantId);
        }

        foreach ($conditions as $field => $value) {
            $this->db->bind(":{$field}", $value);
        }

        $result = $this->db->fetch();
        return (int)$result['count'];
    }

    /**
     * Execute custom query
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function query($sql, $params = [])
    {
        $this->db->query($sql);

        foreach ($params as $param => $value) {
            $this->db->bind($param, $value);
        }

        return $this->db->fetchAll();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        return $this->db->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit()
    {
        return $this->db->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback()
    {
        return $this->db->rollback();
    }
}
