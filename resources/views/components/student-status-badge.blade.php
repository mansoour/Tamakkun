@props(['status'])

{{-- Manual follow-up status (App\Enums\FollowUpStatus). --}}
<x-badge :color="$status->color()" {{ $attributes }}>{{ $status->label() }}</x-badge>
