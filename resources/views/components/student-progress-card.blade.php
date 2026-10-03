@props(['summary'])

<section {{ $attributes->merge(['class' => 'grid gap-4 sm:grid-cols-2 lg:grid-cols-4']) }} aria-label="ملخص التقدّم">
    <x-stat-card label="نسبة الإنجاز" :value="$summary['completion']['percentage'].'%'" icon="chart-bar"
        :hint="'أنجزتِ '.$summary['completion']['completed'].' من '.$summary['completion']['total']" />
    <x-stat-card label="هذا الأسبوع" :value="$summary['weekly']['completed'].'/'.$summary['weekly']['goal']" icon="flag"
        :hint="$summary['weekly']['percentage'].'% من هدف الأسبوع'" />
    <x-stat-card label="أيام متواصلة" :value="$summary['streak']" icon="bolt" hint="أيام فيها نشاط تعلّم متتالية" />
    <x-stat-card label="قيد التقدم" :value="$summary['in_progress']" icon="clock" hint="محتوى بدأتِه ولم تُنجزيه" />
</section>
