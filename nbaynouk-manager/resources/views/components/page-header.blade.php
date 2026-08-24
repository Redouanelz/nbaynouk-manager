@props(['eyebrow' => null, 'title', 'description' => null])
<div class="page-header">
    <div>@if($eyebrow)<p class="eyebrow">{{ $eyebrow }}</p>@endif<h1 class="page-title">{{ $title }}</h1>@if($description)<p class="mt-2 max-w-2xl text-sm leading-6 text-muted">{{ $description }}</p>@endif</div>
    @if(isset($actions))<div class="flex flex-wrap gap-2">{{ $actions }}</div>@endif
</div>
