<x-public-layout title="عن المنصة">
    <article class="card mx-auto max-w-3xl p-6 sm:p-10">
        <h1 class="font-heading text-2xl font-bold text-ink sm:text-3xl">عن {{ $platformName }}</h1>

        <div class="mt-6 space-y-4 leading-loose text-ink">
            @forelse ($paragraphs as $paragraph)
                <p>{!! nl2br(e($paragraph)) !!}</p>
            @empty
                <p>
                    {{ $platformName }} منصة تساعد طالبات الصف الثالث الثانوي على الاستعداد لاختباري القدرات العامة والتحصيلي،
                    وتساعد الموجهة الطلابية على متابعة تقدّمهن.
                </p>
                <p>
                    تجمع المنصة مسارات منظّمة للقدرات الكمي واللفظي والتحصيلي، ومتابعة مواعيد الاختبارات والدرجات،
                    وتنبيهات تساعد الموجهة على معرفة من تحتاج متابعة. لا تعيد المنصة نشر محتوى مملوك لغيرها،
                    وذكر أي مصدر لا يعني شراكة رسمية معه.
                </p>
            @endforelse
        </div>

        @if ($support['email'] || $support['phone'])
            <section aria-labelledby="contact-title" class="mt-8 border-t border-line pt-6">
                <h2 id="contact-title" class="font-heading text-lg font-semibold text-ink">التواصل</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @if ($support['email'])
                        <li class="flex items-center gap-2">
                            <x-icon name="envelope" class="h-5 w-5 text-brand-600" />
                            <a href="mailto:{{ $support['email'] }}" class="text-brand-700 hover:underline" dir="ltr">{{ $support['email'] }}</a>
                        </li>
                    @endif
                    @if ($support['phone'])
                        <li class="flex items-center gap-2">
                            <x-icon name="phone" class="h-5 w-5 text-brand-600" />
                            <a href="tel:{{ preg_replace('/\s+/', '', $support['phone']) }}" class="text-brand-700 hover:underline" dir="ltr">{{ $support['phone'] }}</a>
                        </li>
                    @endif
                </ul>
            </section>
        @endif
    </article>
</x-public-layout>
