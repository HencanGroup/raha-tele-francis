<x-filament-panels::page>
    <form id="form" wire:submit="resetPassword">
        {{ $this->form }}

        <div style="margin-top: 3rem;">
            <x-filament::actions :actions="$this->getFormActions()" :full-width="$this->hasFullWidthFormActions()" />
        </div>
    </form>
</x-filament-panels::page>
