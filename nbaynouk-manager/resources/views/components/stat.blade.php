@props(['label','value','note'=>null])
<div class="stat"><p class="eyebrow">{{ $label }}</p><p class="mt-5 font-serif text-3xl tracking-tight sm:text-4xl">{{ $value }}</p>@if($note)<p class="mt-2 text-xs text-muted">{{ $note }}</p>@endif</div>
