@props(['label','value','note'=>null])
<div class="stat"><p class="stat-label">{{ $label }}</p><p class="stat-value">{{ $value }}</p>@if($note)<p class="stat-note">{{ $note }}</p>@endif</div>
