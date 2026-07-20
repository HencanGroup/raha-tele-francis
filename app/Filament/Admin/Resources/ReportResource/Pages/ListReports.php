<?php

namespace App\Filament\Admin\Resources\ReportResource\Pages;

use App\Filament\Admin\Resources\ReportResource;
use App\Filament\Admin\Resources\ReportResource\Tables\ReportsTable;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

/**
 * Lists all content reports with status and reason filters.
 */
class ListReports extends ListRecords
{
    protected static string $resource = ReportResource::class;

    public function table(Table $table): Table
    {
        return ReportsTable::configure($table);
    }
}
