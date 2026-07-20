<?php

namespace App\Filament\Admin\Resources\EscortResource\Pages;

use App\Filament\Admin\Resources\EscortResource;
use App\Filament\Admin\Resources\EscortResource\Schemas\EscortInfolist;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Read-only detail view of an escort profile with approval-queue actions.
 *
 * Header actions (Approve / Unapprove / Block / Delete) let admins manage
 * verification and user status directly from the view page. The infolist
 * layout is delegated to EscortInfolist for a clean split-file layout.
 */
class ViewEscort extends ViewRecord
{
    protected static string $resource = EscortResource::class;

    /**
     * Eager-load the resources relationship so the Photos tab avoids N+1.
     */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->load('resources');
    }

    /**
     * Header actions for the escort approval queue.
     *
     * Each action delegates to a simple DB transaction rather than a
     * Service for now — these are single-model status flips with no
     * credit or commission side-effects. Extract to EscortVerificationService
     * if cross-cutting logic (email notifications, audit trail) is added.
     *
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [

            // ── Verify ─────────────────────────────────────────────────
            Action::make('verify')
                ->label('Verify')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                // Only shown when the escort is not already verified.
                ->visible(fn (Model $record): bool => $record->verification_status !== 'verified')
                ->action(function (Model $record): void {
                    DB::transaction(function () use ($record): void {
                        $record->update([
                            'verification_status' => 'verified',
                            'is_verified' => true,
                        ]);
                        // Reactivate the user account in case it was previously
                        // suspended or inactive.
                        $record->user->update(['status' => 'active']);
                    });

                    Notification::make()
                        ->title('Escort verified successfully.')
                        ->success()
                        ->send();
                }),

            // ── Unverify ────────────────────────────────────────────────
            Action::make('unverify')
                ->label('Unverify')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('warning')
                // Only shown for escorts that are currently verified.
                ->visible(fn (Model $record): bool => $record->verification_status === 'verified')
                ->requiresConfirmation()
                ->modalHeading('Unverify escort?')
                ->modalDescription('This will mark the escort as unverified.')
                ->action(function (Model $record): void {
                    $record->update([
                        'verification_status' => 'rejected',
                        'is_verified' => false,
                    ]);

                    Notification::make()
                        ->title('Escort has been unverified.')
                        ->warning()
                        ->send();
                }),

            // ── Block ──────────────────────────────────────────────────
            Action::make('block')
                ->label('Block')
                ->icon(Heroicon::OutlinedNoSymbol)
                ->color('danger')
                // Only shown when the user is not already banned.
                ->visible(fn (Model $record): bool => $record->user->status !== 'banned')
                ->requiresConfirmation()
                ->modalHeading('Block escort?')
                ->modalDescription('This will ban the user account and prevent the escort from signing in.')
                ->action(function (Model $record): void {
                    $record->user->update(['status' => 'banned']);

                    Notification::make()
                        ->title('Escort has been blocked.')
                        ->danger()
                        ->send();
                }),

            // ── Delete ─────────────────────────────────────────────────
            // Standard Filament soft-delete with confirmation modal.
            DeleteAction::make()
                ->icon(Heroicon::OutlinedTrash),
        ];
    }

    /**
     * Builds the read-only infolist layout — delegates to EscortInfolist.
     */
    public function infolist(Schema $schema): Schema
    {
        return EscortInfolist::configure($schema);
    }
}
