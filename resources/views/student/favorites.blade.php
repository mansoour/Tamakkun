<x-app-layout area="student" title="المفضلة">
    <x-page-header title="المفضلة" description="المحتوى الذي حفظتِه للرجوع إليه." />

    @if ($contents->isEmpty())
        <x-empty-state class="mt-6" icon="bookmark" title="لا يوجد محتوى في المفضلة" description="اضغطي «أضيفي للمفضلة» في صفحة أي درس أو مقطع." />
    @else
        <div class="mt-6 grid gap-3 md:grid-cols-2">
            @foreach ($contents as $content)
                <x-content-card :content="$content" :status="$statuses->get($content->id)" />
            @endforeach
        </div>
    @endif
</x-app-layout>
