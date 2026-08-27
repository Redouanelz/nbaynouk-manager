@props(['title','description'])
<div class="empty-state"><p class="text-2xl font-bold">{{ $title }}</p><p class="mx-auto mt-2 max-w-md text-sm text-muted">{{ $description }}</p>@if(isset($action))<div class="mt-6">{{ $action }}</div>@endif</div>
