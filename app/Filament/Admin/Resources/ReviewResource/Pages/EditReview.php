<?php

namespace App\Filament\Admin\Resources\ReviewResource\Pages;

use App\Filament\Admin\Resources\ReviewResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Edit a review — primarily used for moderation (verify / hide).
 *
 * On save, recalculates the escort's aggregate rating so the public profile
 * reflects the updated moderation state immediately.
 */
class EditReview extends EditRecord
{
    protected static string $resource = ReviewResource::class;

    /**
     * After saving moderation changes, update the escort's aggregate rating.
     */
    protected function afterSave(): void
    {
        $this->record->escort->updateRating();
    }

    /**
     * @return array<int, DeleteAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
