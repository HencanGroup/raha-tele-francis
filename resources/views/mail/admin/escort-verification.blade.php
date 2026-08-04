<x-mail::message>
# {{ __('admin/mail.escort_verification.greeting', ['name' => $escort->stage_name ?: $escort->user?->name]) }}

@if($approved)
{{ __('admin/mail.escort_verification.approved_body') }}
@else
{{ __('admin/mail.escort_verification.rejected_body') }}
@if($reason)
{{ __('admin/mail.escort_verification.reason_label') }}: **{{ $reason }}**
@endif
@endif

<x-mail::button :url="url('/login')">
{{ __('admin/mail.escort_verification.login_button') }}
</x-mail::button>

{{ __('admin/mail.escort_verification.outro') }}

{{ __('admin/mail.escort_verification.thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
