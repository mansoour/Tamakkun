<x-app-layout area="student" title="مكتبة المقاطع">
    <x-page-header title="مكتبة المقاطع" description="مقاطع من مصادر رسمية أو مصرّح بها." />

    <form method="GET" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="sm:w-72"><x-form.input name="q" label="بحث" :value="request('q')" placeholder="عنوان المقطع" /></div>
        <div class="sm:w-60">
            <x-form.select name="section" label="القسم" :value="request('section')" placeholder="كل الأقسام"
                :options="collect($sections)->mapWithKeys(fn ($s) => [$s->value => $s->label()])" />
        </div>
        <button class="btn-primary">عرض</button>
    </form>

    @if ($videos->isEmpty())
        <x-empty-state class="mt-6" icon="play-circle" title="لا توجد مقاطع منشورة بعد" />
    @else
        <div class="mt-6 grid gap-3 md:grid-cols-2">
            @foreach ($videos as $video)
                <x-content-card :content="$video" :status="$statuses->get($video->id)" />
            @endforeach
        </div>
        {{ $videos->links() }}
    @endif
</x-app-layout>
