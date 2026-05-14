@component('mail::message')
# Szia, {{ $name }}!

Jelszó-visszaállítási kérelmet kaptunk a fiókodhoz. Új jelszót az alábbi
gombra kattintva tudsz beállítani:

@component('mail::button', ['url' => $resetUrl, 'color' => 'primary'])
Új jelszó beállítása
@endcomponent

A link **60 percig** érvényes. Ha lejár, kérj újat a bejelentkezési oldalon.

Ha nem te kezdeményezted a jelszó-visszaállítást, hagyd figyelmen kívül ezt
a levelet — a jelszavad nem változik addig, amíg valaki nem kattint a fenti
gombra és nem ad meg egy új jelszót.

Üdvözlettel,<br>
{{ config('app.name') }}

@component('mail::subcopy')
Ha a gomb nem működik, másold be ezt a hivatkozást a böngésződbe:
[{{ $resetUrl }}]({{ $resetUrl }})
@endcomponent
@endcomponent
