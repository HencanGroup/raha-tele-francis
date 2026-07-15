<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ConversationResource\Pages\ListConversations;
use App\Filament\Admin\Resources\ConversationResource\Pages\ViewConversation;
use App\Models\Conversation;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

/**
 * Manages chat conversations in the admin panel.
 *
 * This resource is **read-only** — conversations are created by the chat system
 * (API). Admins can view conversation details (participants, per-side flags,
 * paid-chat financials) for support and moderation. No create/edit/delete is
 * exposed — conversations are managed through the frontend API.
 */
class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    protected static ?int $navigationSort = 2;

    /* ── Navigation ── */

    public static function getModelLabel(): string
    {
        return 'Conversation';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Conversations';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Moderation';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-chat-bubble-oval-left-ellipsis';
    }

    /* ── Authorisation overrides (read-only) ── */

    /**
     * Conversations are created by the chat system, not the admin panel.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Conversation state is managed by the participants through the API.
     */
    public static function canEdit($record): bool
    {
        return false;
    }

    /**
     * Conversations are soft-deleted — preserve the audit trail in the panel.
     */
    public static function canDelete($record): bool
    {
        return false;
    }

    /* ── Query & Pages ── */

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['userOne', 'userTwo']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConversations::route('/'),
            'view' => ViewConversation::route('/{record}'),
        ];
    }
}
