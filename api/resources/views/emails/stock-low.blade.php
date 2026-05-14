@component('mail::message')
# Készletriasztás

A **{{ $product->name }}** termék készlete a megadott küszöbszint
({{ $threshold }} db) alá süllyedt egy friss rendelést követően.

@component('mail::panel')
**Termék:** {{ $product->name }}
**Maradék készlet:** {{ $product->stock_quantity }} db
**Küszöb:** {{ $threshold }} db
@endcomponent

Ideje feltölteni — ha az utolsó darabok is elfogynak, a webshop a
terméket automatikusan nem fogja eladhatóként megjeleníteni (a
rendelés ellenőrzi a `stock_quantity` mezőt vásárláskor).

Üdvözlettel,<br>
a rendszer

@component('mail::subcopy')
Ezt az e-mailt belső ügyviteli riasztásként küldjük az admin
fiókoknak. Az érintett admin fiókokat az `admins` tábla
tartalmazza.
@endcomponent
@endcomponent
