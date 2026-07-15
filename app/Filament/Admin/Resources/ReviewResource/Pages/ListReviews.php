<?php

namespace App\Filament\Admin\Resources\ReviewResource\Pages;

use App\Filament\Admin\Resources\ReviewResource;
use App\Filament\Admin\Resources\ReviewResource\Tables\ReviewsTable;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

/**
 * Lists all reviews with moderation controls in the row actions.
 */
class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;

    public function table(Table $table): Table
    {
        return ReviewsTable::configure($table);
    }
}
