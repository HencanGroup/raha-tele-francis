<?php

namespace App\Filament\Admin\Resources\MpesaPaymentResource\Pages;

use App\Filament\Admin\Resources\MpesaPaymentResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Read-only detail view of a single M-Pesa payment record.
 *
 * Shows transaction ID, user, phone, amount, credits awarded, status, and
 * the Daraja reference.
 */
class ViewMpesaPayment extends ViewRecord
{
    protected static string $resource = MpesaPaymentResource::class;

    /**
     * Builds the read-only infolist layout for an M-Pesa payment.
     */
    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment Details')
                    ->schema([
                        TextEntry::make('transaction_id')->label('Transaction ID'),
                        TextEntry::make('user.email')->label('User'),
                        TextEntry::make('phone_number')->label('Phone'),
                        TextEntry::make('amount')->label('Amount (KES)')->numeric(2),
                        TextEntry::make('credits_awarded')->label('Credits Awarded')->numeric(2),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'completed' => 'success',
                                'pending' => 'warning',
                                'failed' => 'danger',
                                'cancelled' => 'gray',
                                default => 'gray',
                            }),
                        TextEntry::make('reference')->label('Reference'),
                        TextEntry::make('created_at')->label('Date')->dateTime(),
                    ]),
            ]);
    }
}
