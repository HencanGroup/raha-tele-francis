<?php

namespace App\Filament\Admin\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;

class MustResetPassword extends Page
{
    protected string $view = 'filament.admin.pages.auth.must-reset-password';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $layout = 'filament-panels::components.layout.simple';

    public ?string $password = '';

    public ?string $passwordConfirmation = '';

    protected function getRedirectUrl(): string
    {
        return '/admin-panel';
    }

    public function mount(): void
    {
        if (! auth()->user()->is_temp_password) {
            redirect()->to($this->getRedirectUrl());
        }
    }

    public function resetPassword(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $data = $this->form->getState();

        auth()->user()->update([
            'password' => Hash::make($data['password']),
            'is_temp_password' => false,
        ]);

        Notification::make()
            ->title(__('admin/settings.must_reset_password.notification.success_title'))
            ->body(__('admin/settings.must_reset_password.notification.success_body'))
            ->success()
            ->send();

        redirect()->to($this->getRedirectUrl());
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('admin/settings.must_reset_password.notification.rate_limit_title'))
            ->body(__('admin/settings.must_reset_password.notification.rate_limit_body', ['seconds' => $exception->secondsUntilAvailable]))
            ->danger();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
        ]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('admin/settings.must_reset_password.field.new_password.label'))
            ->password()
            ->revealable()
            ->required()
            ->rule(PasswordRule::default())
            ->same('passwordConfirmation');
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label(__('admin/settings.must_reset_password.field.confirm_password.label'))
            ->password()
            ->revealable()
            ->required()
            ->dehydrated(false);
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin/settings.must_reset_password.title');
    }

    public function getHeading(): string|Htmlable
    {
        return __('admin/settings.must_reset_password.heading');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('resetPassword')
                ->label(__('admin/settings.must_reset_password.action.update_password'))
                ->submit('resetPassword'),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }
}
