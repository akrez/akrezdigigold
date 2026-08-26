@extends('layouts.app')

@section('title', 'Akrez Gold')

@section('content')

    <div class="container-fluid">
        <div x-data="summary()">
            <template x-if="carats.length">
                <div class="row">
                    <div class="col-12 col-xl-8 mx-auto">

                        <ul class="nav nav-pills nav-fill gap-0 pt-2">
                            <template x-for="carat in carats" :key="carat.name">
                                <li class="nav-item">
                                    <button type="button" class="nav-link w-100"
                                        :class="activeCarat === carat.name ? 'active' : ''"
                                        @click="activeCarat = carat.name" x-text="carat.trans"></button>
                                </li>
                            </template>
                        </ul>

                        <template x-if="sources.length">
                            <ul class="nav nav-pills nav-fill gap-0 pt-2">
                                <template x-for="source in sources" :key="source.name">
                                    <li class="nav-item">
                                        <button type="button" class="nav-link w-100"
                                            :class="activeSource === source.name ? 'active' : ''"
                                            @click="activeSource = source.name" x-text="source.trans"></button>
                                    </li>
                                </template>
                            </ul>
                        </template>





                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mt-2">
                                <thead class="bg-200 text-900">
                                    <tr class="table-dark">
                                        <th></th>
                                        <th>قیمت هر گرم</th>
                                        <th>وزن</th>
                                        <th>قیمت</th>
                                    </tr>
                                </thead>
                                <template x-for="scrap in scraps" :key="scrap.source.name">
                                    <template x-for="(variants, variantCarat) in scrap.variants" :key="variantCarat">
                                        <template x-for="(variant, index) in variants.slice(0, 10)" :key="variant.id">
                                            <tbody :class="{'table-secondary': index % 2 == 1}"
                                                x-show="activeSource === scrap.source.name && activeCarat === variantCarat">
                                                <tr>
                                                    <td rowspan="2" class="text-center p-0">
                                                        <img :src="variant.img" class="max-50px" alt="">
                                                    </td>
                                                    <td colspan="2">
                                                        <a class="text-decoration-none" target="_blank"
                                                            :href="variant.url" x-text="variant.ttl"></a>
                                                    </td>
                                                    <td x-text="variant.sel"></td>
                                                </tr>
                                                <tr>
                                                    <td class="font-monospace" x-text="variant.ppgf"></td>
                                                    <td class="font-monospace" x-text="variant.siz"></td>
                                                    <td class="font-monospace" x-text="variant.prcf"></td>
                                                </tr>
                                            </tbody>
                                        </template>
                                    </template>
                                </template>
                            </table>
                        </div>







                    </div>
                </div>
            </template>
        </div>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('summary', (summary) => ({
                carats: [],
                scraps: [],
                sources: [],
                activeCarat: '',
                activeSource: '',
                init() {
                    data = @json($summary);
                    this.carats = data.carats || [];
                    this.scraps = data.scraps || [];
                    this.scraps.forEach(scrap => {
                        this.sources.push(scrap.source);
                    });
                    this.activeCarat = this.carats[0]?.name || '';
                    this.activeSource = this.sources[0]?.name || '';
                },
            }));
        });
    </script>

@endsection
