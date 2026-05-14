@props(['url'])
{{--
    Buttercup email header. Replaces the framework's plain text header
    with a brand-marked variant: a small dusty-rose ✦ glyph next to the
    wordmark, plus a sage-beige hairline divider below. Styles live in
    themes/default.css; inline styles are kept as backups for mail
    clients that strip <head> styles (Gmail, Outlook desktop, etc.).
--}}
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
    <span class="logo-mark" style="color: #b9676b; font-size: 22px; margin-right: 12px; vertical-align: -2px;">✦</span><span style="color: #2a2518; font-family: 'Cormorant Garamond', 'Playfair Display', Georgia, serif; font-size: 26px; font-weight: 400; letter-spacing: 0.22em; text-transform: uppercase;">{{ $slot }}</span>
</a>
</td>
</tr>
<tr>
<td class="header-divider" style="text-align: center; padding: 4px 0 28px;">
    <hr style="border: 0; border-top: 1px solid #cfb586; margin: 0 auto; width: 72px;">
</td>
</tr>
