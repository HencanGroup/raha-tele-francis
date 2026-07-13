<x-mail::message>
# {{ __('admin/mail.user.greeting', ['name' => $user->first_name ?: $user->name]) }}

{{ __('admin/mail.user.intro') }}

{{ __('admin/mail.user.email_label') }}: **{{ $user->email }}**  
@isset($password)
{{ __('admin/mail.user.password_label') }}: **{{ $password }}**
@endisset

<x-mail::button :url="$verificationUrl">
{{ __('admin/mail.user.verify_button') }}
</x-mail::button>

{{ __('admin/mail.user.outro') }}

{{ __('admin/mail.user.thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
