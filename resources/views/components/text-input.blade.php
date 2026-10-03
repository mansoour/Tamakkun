@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'block w-full min-h-[44px] rounded-xl border-line bg-white text-ink shadow-sm placeholder:text-muted/70 focus:border-brand-600 focus:ring-brand-600 disabled:bg-brand-50']) }}>
