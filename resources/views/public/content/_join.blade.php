@guest
    <x-alert type="info" class="mt-6">
        تصفّحي المحتوى بحرية. لمشاهدة الدروس وفتح الألعاب والاختبارات والملفات ومتابعة تقدّمك
        @if ($registrationOpen)
            <a href="{{ route('register') }}" class="font-bold underline underline-offset-4">أنشئي حسابًا مجانيًا</a> أو
        @endif
        <a href="{{ route('login') }}" class="font-bold underline underline-offset-4">سجّلي الدخول</a>.
    </x-alert>
@endguest
