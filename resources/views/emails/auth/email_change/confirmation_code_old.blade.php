<x-mail::message>
# Email change

Use the following code to confirm your current email.

## {{$verificationCode}}

<!-- <x-mail::button :url="''">
Button Text
</x-mail::button> -->

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>