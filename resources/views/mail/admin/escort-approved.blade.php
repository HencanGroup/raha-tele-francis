<x-mail::message>
# {{ __('admin/mail.escort_approved.greeting', ['name' => $user->first_name ?: $user->name]) }}

{{ __('admin/mail.escort_approved.body') }}

{{ __('admin/mail.escort_approved.verify_instruction') }}

<x-mail::button :url="$verificationUrl">
{{ __('admin/mail.escort_approved.verify_button') }}
</x-mail::button>

{{ __('admin/mail.escort_approved.outro') }}

{{ __('admin/mail.escort_approved.thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
