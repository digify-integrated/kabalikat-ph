<nav class="menu menu-rounded menu-active-bg menu-state-primary menu-column menu-lg-row menu-title-gray-700 menu-icon-gray-500 menu-arrow-gray-500 menu-bullet-gray-500 my-5 my-lg-0 align-items-stretch fw-semibold px-2 px-lg-0"
     id="kt_app_header_menu"
     data-kt-menu="true">

    @php
        $tree = $nav_tree ?? [];
    @endphp

    @foreach($tree as $item)
        @include('partials.nav-item', ['item' => $item, 'level' => 0])
    @endforeach
</nav>
