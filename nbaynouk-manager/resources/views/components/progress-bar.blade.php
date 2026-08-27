@props(['value' => 0, 'compact' => false])
<div {{ $attributes->class(['project-progress', 'is-complete' => (int) $value === 100, 'is-compact' => $compact]) }} role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $value }}"><span style="width: {{ max(0, min(100, (int) $value)) }}%"></span></div>
