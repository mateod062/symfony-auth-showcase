<?php

namespace App\Service\UserExport\Interface;

interface UserExportServiceInterface
{
    /**
     * Export users to CSV
     *
     * @param array $filter
     * @return string
     */
    public function exportToCsv(array $filter): string;

    /**
     * Export users to PDF
     *
     * @param $filter
     * @return string
     */
    public function exportToPdf($filter): string;
}