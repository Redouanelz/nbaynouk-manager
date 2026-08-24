@props(['value'])
@php
    $key = $value instanceof \BackedEnum ? $value->value : (string) $value;
    $label = method_exists($value, 'label') ? $value->label() : $value;
    $tone = match($key) { 'paid','completed','suivi' => 'positive', 'overdue','cancelled' => 'danger', 'waiting','partial','launch','onboarding' => 'warning', default => 'neutral' };
@endphp
<span class="badge badge-{{ $tone }}">{{ $label }}</span>
