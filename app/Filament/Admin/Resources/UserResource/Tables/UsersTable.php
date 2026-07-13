<?php

namespace App\Filament\Admin\Resources\UserResource\Tables;

use App\Filament\Concerns\HasDateRangeFilter;
use App\Filament\Exports\UserExporter;
use App\Services\Admin\UserService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Table schema for the Admin → Users (staff) resource.
 *
 * Composes columns, a "created" date-range filter, per-row actions
 * (suspend / activate / force password reset), and a bulk-actions dropdown
 * (suspend / assign role / delete / export). Every state-changing action
 * delegates to UserService — this class never holds business logic.
 */
class UsersTable
{
    // Shared from/to date-range filter — see "Filament Table Rules" in AGENTS.md.
    use HasDateRangeFilter;

    /**
     * Apply the Users table configuration.
     *
     * Only platform staff are listed; newest first. Roles are eager-loaded in
     * UserResource::getEloquentQuery() so the Roles badge column avoids N+1.
     */
    public static function configure(Table $table): Table
    {
        return $table
            // Only platform staff belong in this table — escorts and members
            // are managed via EscortResource / MemberResource and the API.
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('user_type', 'system_user'))
            ->columns(self::columns())
            ->filters(self::filters())
            ->defaultSort('created_at', 'desc')
            ->recordActions(self::recordActions())
            ->toolbarActions(self::bulkActions())
            ->recordUrl(null);
    }

    /**
     * Table columns: identity, status, roles, and audit timestamps.
     *
     * @return array<int, TextColumn>
     */
    protected static function columns(): array
    {
        return [
            TextColumn::make('id')
                ->label('ID')
                ->sortable()
                ->toggleable(),

            TextColumn::make('name')
                ->label('Name')
                ->searchable()
                ->sortable(),

            TextColumn::make('user_type')
                ->label('Type')
                ->badge()
                ->color('info'),

            TextColumn::make('email')
                ->label('Email')
                ->searchable()
                ->sortable(),

            TextColumn::make('phone_number')
                ->label('Phone'),

            TextColumn::make('status')
                ->label('Status')
                ->badge()
                // Colour signals account state at a glance.
                ->color(fn (string $state): string => match ($state) {
                    'active' => 'success',
                    'inactive' => 'warning',
                    'suspended' => 'danger',
                    default => 'gray',
                }),

            // Spatie roles — eager-loaded upstream to avoid an N+1 per row.
            TextColumn::make('roles.name')
                ->label('Roles')
                ->badge()
                ->separator(',')
                ->color('primary'),

            TextColumn::make('email_verified_at')
                ->label('Email Verified')
                ->dateTime()
                ->sortable()
                ->toggleable(),

            TextColumn::make('created_at')
                ->label('Created At')
                ->dateTime()
                ->sortable()
                ->toggleable(),
        ];
    }

    /**
     * Table filters: a created-at date range.
     *
     * @return array<int, Filter>
     */
    protected static function filters(): array
    {
        return [
            // Account-created date range — parsing/clamping delegated to the trait.
            Filter::make('created_at')
                ->schema([
                    DatePicker::make('from')->label('Created from'),
                    DatePicker::make('to')->label('Created until'),
                ])
                ->query(fn (Builder $query, array $data): Builder => self::applyDateRangeFilter(
                    $query,
                    $data,
                    column: 'created_at',
                    defaultMonths: 2,
                    maxMonths: 3,
                )),
        ];
    }

    /**
     * Per-row actions: edit, plus a suspend/activate/reset group.
     *
     * @return array<int, mixed>
     */
    protected static function recordActions(): array
    {
        return [
            EditAction::make(),
            ActionGroup::make([
                // Delegates to UserService::suspend(); hidden if already suspended.
                Action::make('suspend')
                    ->label('Suspend')
                    ->action(function ($record, UserService $userService) {
                        $userService->suspend($record);
                        Notification::make()
                            ->success()
                            ->title('User has been suspended.')
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->status !== 'suspended'),

                // Delegates to UserService::activate(); hidden if already active.
                Action::make('activate')
                    ->label('Activate')
                    ->action(function ($record, UserService $userService) {
                        $userService->activate($record);
                        Notification::make()
                            ->success()
                            ->title('User has been activated.')
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->status !== 'active'),

                // Delegates to UserService::forcePasswordReset() (emails a reset link).
                Action::make('force_password_reset')
                    ->label('Force Password Reset')
                    ->action(function ($record, UserService $userService) {
                        $userService->forcePasswordReset($record);
                        Notification::make()
                            ->success()
                            ->title('Password reset email has been sent.')
                            ->send();
                    })
                    ->requiresConfirmation(),
            ]),
        ];
    }

    /**
     * Bulk actions dropdown: suspend, assign role, delete, and export.
     *
     * @return array<int, BulkActionGroup>
     */
    protected static function bulkActions(): array
    {
        return [
            BulkActionGroup::make([
                // Suspends each selected user via UserService::suspend().
                BulkAction::make('bulk_suspend')
                    ->label('Suspend')
                    ->action(function (Collection $records, UserService $userService) {
                        $records->each(fn ($record) => $userService->suspend($record));
                        Notification::make()
                            ->success()
                            ->title(count($records).' users suspended.')
                            ->send();
                    })
                    ->requiresConfirmation(),

                // Grants one Spatie role to every selected user.
                BulkAction::make('assign_role')
                    ->label('Assign Role')
                    ->schema([
                        Select::make('role')
                            ->label('Roles')
                            ->relationship('roles', 'name')
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $records->each(fn ($record) => $record->assignRole($data['role']));
                        Notification::make()
                            ->success()
                            ->title('Role assigned to selected users.')
                            ->send();
                    }),

                DeleteBulkAction::make(),

                // Export the selected staff (or the full filtered set).
                ExportBulkAction::make()
                    ->exporter(UserExporter::class)
                    // File name carries exports.id so support can trace it back.
                    ->fileName(fn (Export $export): string => "users-{$export->getKey()}"),
            ]),
        ];
    }
}
