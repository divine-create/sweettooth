<?php

namespace App\Services\Reports\Definitions;

interface ReportDefinition
{
    /**
     * Metadata for the report definition.
     * Expected keys: name, type, category, requires_department, permissions.
     */
    public function meta(): array;

    /**
     * Query and return raw data for the report.
     */
    public function query(array $context): array;

    /**
     * Build summary metrics from raw data.
     */
    public function summary(array $data, array $context): array;

    /**
     * Build chart datasets from raw data.
     */
    public function charts(array $data, array $context): array;

    /**
     * Build display tables from raw data and summary metrics.
     */
    public function tables(array $data, array $summary, array $context): array;

    /**
     * Build narrative insights from raw data and summary metrics.
     */
    public function narrative(array $data, array $summary, array $context): array;
}
