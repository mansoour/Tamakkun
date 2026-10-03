@props(['action', 'confirm' => 'هل أنتِ متأكدة من الحذف؟ لا يمكن التراجع عن هذا الإجراء.'])

<form method="POST" action="{{ $action }}" x-data x-on:submit="if (! window.confirm(@js($confirm))) $event.preventDefault()">
    @csrf
    @method('DELETE')
    <button type="submit" {{ $attributes->merge(['class' => 'btn min-h-[40px] px-3 text-red-700 hover:bg-red-50']) }}>
        <x-icon name="x-circle" class="h-4 w-4" />
        حذف
    </button>
</form>
