<?php
// FILE: /app/helpers/Slug.php

/**
 * Slug helper class
 *
 * Generates URL-friendly slugs from strings.
 */
class Slug
{
    /**
     * Generate slug from string
     *
     * @param string $text
     * @param string $separator
     * @return string
     */
    public static function generate($text, $separator = '-')
    {
        // Convert to lowercase
        $text = strtolower($text);

        // Replace non-alphanumeric characters with separator
        $text = preg_replace('/[^a-z0-9]+/', $separator, $text);

        // Remove multiple consecutive separators
        $text = preg_replace('/' . preg_quote($separator) . '+/', $separator, $text);

        // Trim separators from ends
        $text = trim($text, $separator);

        return $text;
    }

    /**
     * Generate unique slug for table
     *
     * @param string $text
     * @param string $table
     * @param string $column
     * @param int|null $excludeId
     * @param int|null $tenantId
     * @return string
     */
    public static function generateUnique($text, $table, $column = 'slug', $excludeId = null, $tenantId = null)
    {
        $slug = self::generate($text);
        $originalSlug = $slug;
        $counter = 1;

        $db = Database::getInstance();

        while (true) {
            $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = :slug";

            if ($tenantId !== null) {
                $sql .= " AND tenant_id = :tenant_id";
            }

            if ($excludeId !== null) {
                $sql .= " AND id != :exclude_id";
            }

            $db->query($sql);
            $db->bind(':slug', $slug);

            if ($tenantId !== null) {
                $db->bind(':tenant_id', $tenantId);
            }

            if ($excludeId !== null) {
                $db->bind(':exclude_id', $excludeId);
            }

            $result = $db->fetch();

            if ($result['count'] == 0) {
                break;
            }

            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
