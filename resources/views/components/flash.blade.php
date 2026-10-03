@if (session('success'))
    <x-alert type="success" class="mb-6">{{ session('success') }}</x-alert>
@endif
@foreach (['delete', 'account_status', 'import'] as $key)
    @error($key)
        <x-alert type="danger" class="mb-6">{{ $message }}</x-alert>
    @enderror
@endforeach
