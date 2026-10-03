{{-- Marks a page whose real functionality arrives in a later phase. --}}
<x-alert type="warning" title="صفحة قيد التطوير" {{ $attributes }}>
    {{ $slot->isEmpty() ? 'هذه واجهة أولية للتطوير فقط، وستُضاف وظائفها الفعلية في المراحل القادمة.' : $slot }}
</x-alert>
