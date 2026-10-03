@props(['disabled' => false])

<div x-data="{ visible: false }" class="relative">
    <input :type="visible ? 'text' : 'password'" type="password" @disabled($disabled)
        {{ $attributes->merge(['class' => 'block w-full min-h-[44px] rounded-xl border-line bg-white pe-12 text-ink shadow-sm focus:border-brand-600 focus:ring-brand-600']) }}>
    <button type="button" x-on:click="visible = ! visible"
        class="absolute inset-y-0 end-0 flex w-12 items-center justify-center rounded-e-xl text-muted hover:text-brand-700"
        :aria-label="visible ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'" aria-label="إظهار كلمة المرور"
        :aria-pressed="visible.toString()" aria-pressed="false">
        <x-icon name="eye" x-show="! visible" />
        <x-icon name="eye-slash" x-show="visible" x-cloak />
    </button>
</div>
