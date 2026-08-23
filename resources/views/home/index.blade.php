@extends('layouts.app')

@section('title', 'Akrez Gold')

@php
    $summaryItems = array_filter(data_get($summary, 'items', []));
    $firstCaratKey = array_key_first($summaryItems);
@endphp

@section('content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-12 col-xl-8 mx-auto">
                <div class="alert alert-info my-3 text-center" dir="ltr" role="alert">
                    {{ data_get($summary, 'date') }}
                </div>
            </div>
        </div>

        @if (count($summaryItems))
            <div class="row">
                <div class="col-12 col-xl-8 mx-auto">

                    <ul class="nav nav-pills nav-fill" role="tablist">
                        @foreach ($summaryItems as $caratKey => $items)
                            <li class="nav-item">
                                <a
                                    class="nav-link {{ $caratKey === $firstCaratKey ? 'active' : '' }}"
                                    data-bs-toggle="tab"
                                    href="#tab-{{ $caratKey }}"
                                    role="tab"
                                    aria-controls="tab-{{ $caratKey }}"
                                    aria-selected="{{ $caratKey === $firstCaratKey ? 'true' : 'false' }}"
                                >
                                    عیار {{ match ($caratKey) {
                                        'CARAT_9999' => '999.9',
                                        default => str_replace('CARAT_', '', $caratKey),
                                    } }}
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content border-x px-0 pt-3">
                        @foreach ($summaryItems as $caratKey => $items)
                            <div
                                class="tab-pane fade table-responsive {{ $caratKey === $firstCaratKey ? 'show active' : '' }}"
                                id="tab-{{ $caratKey }}"
                                role="tabpanel"
                            >
                                <table class="table table-bordered table-sm align-middle">
                                    <thead class="bg-200 text-900 table-dark">
                                        <tr>
                                            <th></th>
                                            <th>قیمت هر گرم</th>
                                            <th>قیمت</th>
                                            <th>وزن</th>
                                            <th>فروشنده</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (array_slice($items, 0, 20) as $item)
                                            @php($rowTheme = $loop->index % 2 ? ' table-secondary ' : '')
                                            <tr class="{{ $rowTheme }}">
                                                <td rowspan="2" class="text-center p-0">
                                                    <img src="{{ $item['img'] }}" class="max-50px" alt="">
                                                </td>
                                                <td colspan="3">
                                                    <a class="text-decoration-none" target="_blank" href="{{ $item['url'] }}">{{ $item['ttl'] }}</a>
                                                </td>
                                                <td>{{ $item['sel'] }}</td>
                                            </tr>
                                            <tr class="{{ $rowTheme }}">
                                                <td class="font-monospace">{{ $item['ppgf'] }}</td>
                                                <td class="font-monospace">{{ $item['prcf'] }}</td>
                                                <td class="font-monospace">{{ $item['siz'] }}</td>
                                                <td>{{ $item['src'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>

                </div>
            </div>
        @endif

    </div>

@endsection
