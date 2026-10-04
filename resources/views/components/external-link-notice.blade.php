{{--
    Privacy warning shown wherever the platform sends a student to another
    website (forms, games, files, links). See docs/security.md#external-links.
    `officialException` adds a line for lists that may contain official
    services (such as قياس), where real details are required.
--}}
@props(['officialException' => false])

<x-alert type="warning" title="تنبيه قبل فتح الرابط" {{ $attributes }}>
    هذا الرابط يفتح موقعًا خارج المنصة. لا تُدخلي بياناتك الشخصية الحقيقية مثل رقم الجوال أو رقم الهوية أو العنوان أو كلمة المرور.
    إذا طُلب منكِ رقم جوال فاكتبي رقمًا وهميًا مثل <span dir="ltr" class="font-bold">0500000000</span>، ويكفي الاسم الأول إذا طُلب الاسم.
    @if ($officialException)
        <span class="mt-1 block">يُستثنى من ذلك ما عليه علامة «مصدر رسمي» مثل منصة قياس، فأدخلي فيه بياناتك الصحيحة.</span>
    @endif
</x-alert>
