@component('mail::message')
# Üdv nálunk, {{ $name }}! ✦

Köszönjük, hogy csatlakoztál a **Buttercup Perfumery** közösségéhez.
A parfümjeink minden egyes cseppje egy történet — természetes
összetevőkből, gondosan válogatott eredetből, hogy a bőrödön egy
új illatemlék szülessen.

@if ($coupon)
@component('mail::panel')
**🎁 Köszöntő ajánlatunk neked**

Használd ezt a kuponkódot az első rendelésednél:

**Kód:** `{{ $coupon->coupon_code }}`
**Kedvezmény:** {{ $discountLabel }}
@if ($expiryLabel)
**Érvényesség:** {{ $expiryLabel }}-ig
@endif
@endcomponent
@else
**Friss érkezők.** Ősszel három új illat csatlakozott a kollekcióhoz —
egy földes vetiver, egy meleg ámbra-narancs és egy pasztell ibolya.
Nézz körül, és találd meg a hozzád illőt.
@endif

@component('mail::button', ['url' => $shopUrl, 'color' => 'primary'])
Fedezd fel a kollekciót
@endcomponent

## Amit nálunk találsz

- **Természetes alapanyagok** — bergamot Calabriából, rózsa Marokkóból, cédrus az Atlasz-hegységből.
- **Vegán, állatkísérlet-mentes** — minden illat, kivétel nélkül.
- **Ingyenes szállítás** az EU-n belül 150 € felett.

A regisztrációd után küldött **megerősítő e-mailt** is hamarosan
megkapod — kérlek, koppints rá a fiók aktiválásához.

Üdvözlettel,<br>
a **{{ config('app.name') }}** csapata

@component('mail::subcopy')
Ezt az e-mailt azért kapod, mert nemrég regisztráltál a
Buttercup Perfumery oldalán. Ha nem te voltál, kérjük, hagyd
figyelmen kívül a levelet.
@endcomponent
@endcomponent
