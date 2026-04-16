<ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 pt-1">
    <li class="breadcrumb-item text-muted">
        <a href="{{ url('/app') }}" class="text-muted text-hover-primary">
            Home
        </a>
    </li>

    @foreach(($bc_items ?? []) as $index => $item)
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-300 w-5px h-2px"></span>
        </li>

        @php
            $isNavCrumb = !is_null($item['id']);
            $isLast = $loop->last;
        @endphp

        <li class="breadcrumb-item {{ $isLast ? 'text-gray-900' : 'text-muted' }}">
           {{ $item['label'] }}
        </li>
    @endforeach
</ul>