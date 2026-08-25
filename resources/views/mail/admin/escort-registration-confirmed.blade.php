<x-mail::message>
# {{ __('admin/mail.escort_registration_confirmed.greeting', ['name' => $user->first_name ?: $user->name]) }}

{{ __('admin/mail.escort_registration_confirmed.body') }}

{{ __('admin/mail.escort_registration_confirmed.next_steps') }}

{{ __('admin/mail.escort_registration_confirmed.outro') }}

{{ __('admin/mail.escort_registration_confirmed.thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
