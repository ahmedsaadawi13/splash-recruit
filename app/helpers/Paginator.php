<?php
// FILE: /app/helpers/Paginator.php

/**
 * Paginator helper class
 *
 * Provides pagination functionality for database queries.
 */
class Paginator
{
    private $totalRecords;
    private $perPage;
    private $currentPage;
    private $totalPages;
    private $offset;

    /**
     * Constructor
     *
     * @param int $totalRecords
     * @param int $perPage
     * @param int $currentPage
     */
    public function __construct($totalRecords, $perPage = 20, $currentPage = 1)
    {
        $this->totalRecords = $totalRecords;
        $this->perPage = $perPage;
        $this->currentPage = max(1, $currentPage);
        $this->totalPages = ceil($totalRecords / $perPage);
        $this->offset = ($this->currentPage - 1) * $perPage;
    }

    /**
     * Get offset for SQL query
     *
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Get limit for SQL query
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->perPage;
    }

    /**
     * Get current page
     *
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * Get total pages
     *
     * @return int
     */
    public function getTotalPages()
    {
        return $this->totalPages;
    }

    /**
     * Get total records
     *
     * @return int
     */
    public function getTotalRecords()
    {
        return $this->totalRecords;
    }

    /**
     * Check if there is a previous page
     *
     * @return bool
     */
    public function hasPrevious()
    {
        return $this->currentPage > 1;
    }

    /**
     * Check if there is a next page
     *
     * @return bool
     */
    public function hasNext()
    {
        return $this->currentPage < $this->totalPages;
    }

    /**
     * Get previous page number
     *
     * @return int|null
     */
    public function getPreviousPage()
    {
        return $this->hasPrevious() ? $this->currentPage - 1 : null;
    }

    /**
     * Get next page number
     *
     * @return int|null
     */
    public function getNextPage()
    {
        return $this->hasNext() ? $this->currentPage + 1 : null;
    }

    /**
     * Get page links array
     *
     * @param int $adjacents Number of adjacent pages to show
     * @return array
     */
    public function getPageLinks($adjacents = 2)
    {
        $links = [];

        // Always show first page
        $links[] = 1;

        // Calculate start and end of range
        $start = max(2, $this->currentPage - $adjacents);
        $end = min($this->totalPages - 1, $this->currentPage + $adjacents);

        // Add ellipsis after first page if needed
        if ($start > 2) {
            $links[] = '...';
        }

        // Add middle pages
        for ($i = $start; $i <= $end; $i++) {
            $links[] = $i;
        }

        // Add ellipsis before last page if needed
        if ($end < $this->totalPages - 1) {
            $links[] = '...';
        }

        // Always show last page if there is more than one page
        if ($this->totalPages > 1) {
            $links[] = $this->totalPages;
        }

        return $links;
    }

    /**
     * Render pagination HTML
     *
     * @param string $baseUrl
     * @return string
     */
    public function render($baseUrl)
    {
        if ($this->totalPages <= 1) {
            return '';
        }

        $html = '<nav class="pagination">';
        $html .= '<ul class="pagination-list">';

        // Previous button
        if ($this->hasPrevious()) {
            $url = $baseUrl . '?page=' . $this->getPreviousPage();
            $html .= '<li><a href="' . htmlspecialchars($url) . '" class="pagination-link">&laquo; Previous</a></li>';
        }

        // Page numbers
        $links = $this->getPageLinks();
        foreach ($links as $page) {
            if ($page === '...') {
                $html .= '<li><span class="pagination-ellipsis">...</span></li>';
            } else {
                $url = $baseUrl . '?page=' . $page;
                $activeClass = $page === $this->currentPage ? ' active' : '';
                $html .= '<li><a href="' . htmlspecialchars($url) . '" class="pagination-link' . $activeClass . '">' . $page . '</a></li>';
            }
        }

        // Next button
        if ($this->hasNext()) {
            $url = $baseUrl . '?page=' . $this->getNextPage();
            $html .= '<li><a href="' . htmlspecialchars($url) . '" class="pagination-link">Next &raquo;</a></li>';
        }

        $html .= '</ul>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Get pagination info text
     *
     * @return string
     */
    public function getInfo()
    {
        $from = $this->offset + 1;
        $to = min($this->offset + $this->perPage, $this->totalRecords);

        return "Showing {$from} to {$to} of {$this->totalRecords} results";
    }
}
