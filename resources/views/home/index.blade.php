@extends('layouts.app')

@section('title', 'Akrez Gold')

@php
    $summaryCarats = data_get($summary, 'carats', []);
    $summaryScraps = data_get($summary, 'scraps', []);
    $summaryItems = data_get($summary, 'items', []);
@endphp

@section('content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-12 col-xl-8 mx-auto">
                <div class="alert alert-info my-3 text-center lh-1" dir="ltr" role="alert">
                    {{ data_get($summary, 'date') }}
                </div>
            </div>
        </div>

        @if (count($summaryCarats))
            <div class="row">
                <div class="col-12 col-xl-8 mx-auto">

                    <ul class="nav nav-pills nav-fill gap-0" role="tablist">
                        @foreach ($summaryCarats as $carat)
                            <li class="nav-item">
                                <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab"
                                    href="#tab-{{ $carat['name'] }}" role="tab" aria-controls="tab-{{ $carat['name'] }}"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $carat['trans'] }}</a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content border-x px-0 pt-3">
                        @foreach ($summaryCarats as $carat)
                            <div class="tab-pane fade table-responsive {{ $loop->first ? 'show active' : '' }}"
                                id="tab-{{ $carat['name'] }}" role="tabpanel">
                                <table class="table table-bordered table-sm align-middle small">
                                    @foreach (data_get($summaryItems, $carat['name'], []) as $sourceName => $items)
                                        @continue(count($items) === 0)
                                        <thead class="bg-200 text-900">
                                            <tr class="table-dark text-center">
                                                <th colspan="4">
                                                    {{ data_get($summaryScraps, $sourceName . '.source.trans', $sourceName) }}
                                                </th>
                                            </tr>
                                            <tr class="table-dark">
                                                <th></th>
                                                <th>قیمت هر گرم</th>
                                                <th>وزن</th>
                                                <th>قیمت</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach (array_slice($items, 0, 5) as $item)
                                                @php($rowTheme = $loop->index % 2 ? ' table-secondary ' : '')
                                                <tr class="{{ $rowTheme }}">
                                                    <td rowspan="2" class="text-center p-0">
                                                        <img src="{{ $item['img'] }}" class="max-50px" alt="">
                                                    </td>
                                                    <td colspan="2">
                                                        <a class="text-decoration-none" target="_blank"
                                                            href="{{ $item['url'] }}">{{ $item['ttl'] }}</a>
                                                    </td>
                                                    <td>{{ $item['sel'] }}</td>
                                                </tr>
                                                <tr class="{{ $rowTheme }}">
                                                    <td class="font-monospace">{{ $item['ppgf'] }}</td>
                                                    <td class="font-monospace">{{ $item['siz'] }}</td>
                                                    <td class="font-monospace">{{ $item['prcf'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    @endforeach
                                </table>
                            </div>
                        @endforeach
                    </div>

                </div>
            </div>
        @endif

    </div>

@endsection
