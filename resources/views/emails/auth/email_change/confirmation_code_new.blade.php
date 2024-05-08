<x-mail::message>
# Email change

Use the following code to confirm your new email.

## {{$verificationCode}}

<x-mail::button :url="$actionUrl">
Confirm
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
