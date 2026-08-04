<?php

namespace App\Filament\Admin\Resources\EscortResource\Pages;

use App\Filament\Admin\Resources\EscortResource;
use App\Filament\Admin\Resources\EscortResource\Schemas\EscortInfolist;
use App\Services\Escort\EscortVerificationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

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
     * Verification state changes (verify / unverify) delegate to
     * EscortVerificationService, which owns the transaction AND emails the
     * escort. Block/Delete stay inline — they are single-user-status flips
     * with no cross-cutting side-effects.
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
                    app(EscortVerificationService::class)->verify($record);

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
                ->modalDescription('This will mark the escort as unverified and notify them.')
                ->action(function (Model $record): void {
                    app(EscortVerificationService::class)->reject($record);

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
