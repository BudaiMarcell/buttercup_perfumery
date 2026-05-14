@component('mail::message')
# Szia, {{ $name }}!

Új kuponkód érkezett a Buttercup Perfumery-ben — neked is jár belőle. 🌿

@component('mail::panel')
**Kód:** `{{ $coupon->coupon_code }}`

**Kedvezmény:** {{ $discountLabel }}

@if ($expiryLabel)
**Érvényesség:** {{ $expiryLabel }}-ig
@endif
@endcomponent

A kódot a pénztárnál tudod megadni — egyetlen kattintás, és máris levonjuk
az árból.

@component('mail::button', ['url' => $shopUrl, 'color' => 'primary'])
Irány a webshop
@endcomponent

Ha kérdésed van, válaszolj erre az e-mailre, és segítünk!

Üdvözlettel,<br>
{{ config('app.name') }}
@endcomponent
