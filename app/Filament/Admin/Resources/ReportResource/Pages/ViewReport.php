<?php

namespace App\Filament\Admin\Resources\ReportResource\Pages;

use App\Filament\Admin\Resources\ReportResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * Read-only detail view of a content report with resolve/dismiss actions.
 */
class ViewReport extends ViewRecord
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [

            // ── Resolve ─────────────────────────────────────────────────
            Action::make('resolve')
                ->label('Resolve')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->visible(fn (Model $record): bool => $record->status === 'pending')
                ->requiresConfirmation()
                ->modalHeading('Resolve this report?')
                ->modalDescription('Marking this report as resolved will remove it from the pending queue.')
                ->action(function (Model $record): void {
                    $record->update([
                        'status' => 'resolved',
                        'resolved_by' => auth()->id(),
                        'resolved_at' => now(),
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Report resolved.')
                        ->success()
                        ->send();
                }),

            // ── Dismiss ────────────────────────────────────────────────
            Action::make('dismiss')
                ->label('Dismiss')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->visible(fn (Model $record): bool => $record->status === 'pending')
                ->requiresConfirmation()
                ->modalHeading('Dismiss this report?')
                ->modalDescription('The report will be marked as dismissed with no further action.')
                ->action(function (Model $record): void {
                    $record->update([
                        'status' => 'dismissed',
                        'resolved_by' => auth()->id(),
                        'resolved_at' => now(),
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Report dismissed.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
