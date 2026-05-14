@component('mail::message')
# {{ $headline }}

Szia, {{ $order->user->name ?? 'kedves vásárlónk' }}!

A **#{{ $order->id }}** rendelésed státusza most:
**{{ \Illuminate\Support\Str::ucfirst($newStatus) }}**.

@if ($newStatus === 'canceled')
A lemondást rögzítettük, és — ha volt készlet-csökkenés — visszahelyeztük a
termékeket a polcra. Ha bankkártyával fizettél, a visszatérítés
néhány munkanapon belül megérkezik.
@elseif ($newStatus === 'shipped')
A csomag jelenleg úton van a megadott szállítási címedre. A futárral
történő egyeztetés érdekében tartsd elérhető közelben a telefonod.
@elseif ($newStatus === 'arrived')
Köszönjük, hogy minket választottál! Ha tetszett az illat, örülnénk
egy értékelésnek a fiókodban — másoknak is nagy segítség.
@endif

@component('mail::panel')
**Összesítő**

- **Végösszeg:** {{ number_format($order->total_amount, 0, ',', ' ') }} Ft
- **Fizetési mód:** {{ $order->payment_method }}
- **Fizetési státusz:** {{ $order->payment_status }}
@endcomponent

@component('mail::button', ['url' => $ordersUrl, 'color' => 'primary'])
Rendelés megtekintése
@endcomponent

Üdvözlettel,<br>
a **{{ config('app.name') }}** csapata
@endcomponent
