@if (session('success'))
    <x-alert type="success" class="mb-6">{{ session('success') }}</x-alert>
@endif
@if (session('info'))
    <x-alert type="info" class="mb-6">{{ session('info') }}</x-alert>
@endif
@foreach (['delete', 'account_status', 'import', 'view_as'] as $key)
    @error($key)
        <x-alert type="danger" class="mb-6">{{ $message }}</x-alert>
    @enderror
@endforeach
