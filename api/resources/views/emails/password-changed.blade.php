@component('mail::message')
# Szia, {{ $name }}!

Tudatjuk, hogy a fiókodhoz tartozó **jelszót sikeresen megváltoztattuk**
{{ $changedAt }}-kor.

Ha te kezdeményezted, nincs teendőd — ezt a levelet csak biztonsági
visszaigazolásnak küldjük.

@component('mail::panel')
**Nem te voltál?**
Azonnal állítsd vissza a jelszavadat a "Forgot password?" linken
keresztül, és minden eszközről jelentkezz ki. Az új jelszó beállításával
minden korábbi bejelentkezést érvénytelenítünk.
@endcomponent

@component('mail::button', ['url' => $forgotUrl, 'color' => 'error'])
Új jelszót kérek (ha nem én voltam)
@endcomponent

Üdvözlettel,<br>
a **{{ config('app.name') }}** csapata

@component('mail::subcopy')
Ezt az e-mailt biztonsági okból kapod, és nem tudsz rá leiratkozni.
Csak fiókváltozások után küldjük.
@endcomponent
@endcomponent
