<?php

namespace App\Filament\Admin\Resources\ConversationResource\Pages;

use App\Filament\Admin\Resources\ConversationResource;
use App\Filament\Admin\Resources\ConversationResource\Tables\ConversationsTable;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

/**
 * Lists all chat conversations — read-only.
 */
class ListConversations extends ListRecords
{
    protected static string $resource = ConversationResource::class;

    public function table(Table $table): Table
    {
        return ConversationsTable::configure($table);
    }
}
