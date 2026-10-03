@if ($paginator->hasPages())
    <nav role="navigation" aria-label="التنقل بين الصفحات" class="mt-6 flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
        <p class="text-sm text-muted">
            عرض {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} من {{ $paginator->total() }}
        </p>

        <ul class="flex flex-wrap items-center gap-1">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="btn-secondary min-h-[40px] px-3 opacity-50" aria-disabled="true">السابق</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-secondary min-h-[40px] px-3">السابق</a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="px-2 text-muted">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="btn min-h-[40px] bg-brand-600 px-3 text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn-secondary min-h-[40px] px-3" aria-label="الصفحة {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-secondary min-h-[40px] px-3">التالي</a>
                @else
                    <span class="btn-secondary min-h-[40px] px-3 opacity-50" aria-disabled="true">التالي</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
