<?php

namespace App\Filament\Admin\Resources\MemberResource\Pages;

use App\Filament\Admin\Resources\MemberResource;
use App\Filament\Admin\Resources\MemberResource\Tables\MembersTable;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

/**
 * Lists all member records. Navigation to the detail view is handled
 * by the recordUrl configured in MembersTable.
 */
class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    public function table(Table $table): Table
    {
        return MembersTable::configure($table);
    }
}
