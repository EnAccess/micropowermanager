@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-xs-12 col-sm-7 col-md-7 col-lg-4">
            <h1 class="page-title txt-color-blueDark">
                <i class="fa fa-list fa fw"></i>
                List
                <span>> of {{$title}}</span>
            </h1>
        </div>
    </div>

    <section id="widget-grid">
        <div class="row">
            <!-- widget start -->
            <article class="col-sm-12 col-xs-12 col-md-12 col-lg-12">
                @component('skeletons.widget', ['widgetID' => 'peopleList', 'widgetIcon' => 'fa-list', 'widgetTitle' => 'People'])
                    <div id="dt_basic_wrapper" class="dataTables_wrapper form-inline dt-bootstrap no-footer">
                        <div class="widget-body-toolbar">
                            <div class="row">
                                <div class="col-xs-9 col-sm-5 col-md-5 col-lg-5">
                                </div>
                                <div class="col-xs-3 col-sm-7 col-md-7 col-lg-7 text-right">
                                    <a href="{{ route('meters.add')}}" class="btn btn-success">
                                        <i class="fa fa-plus"></i> <span class="hidden-mobile">Add New Meter</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <table id="dt_basic" class="table table-striped table-bordered table-hover dataTable no-footer"
                               width="100%"
                               role="grid" aria-describedby="dt_basic_info" style="width: 100%;">
                            <thead>
                            <tr role="row">
                                <th data-hide="phone" class="sorting_asc" tabindex="0" aria-controls="dt_basic"
                                    rowspan="1"
                                    colspan="1"
                                    aria-sort="ascending" aria-label="ID: activate to sort column descending"
                                    style="width: 61px;">ID
                                </th>
                                <th data-class="expand" class="expand sorting" tabindex="0" aria-controls="dt_basic"
                                    rowspan="1"
                                    colspan="1" aria-label=" Name: activate to sort column ascending"
                                    style="width: 141px;">
                                    <i class="fa fa-fw fa-user text-muted hidden-md hidden-sm hidden-xs"></i> Serial
                                    Number
                                </th>

                                <th data-hide="phone" class="sorting" tabindex="0" aria-controls="dt_basic"
                                    rowspan="1" colspan="1" aria-label=" Phone: activate to sort column ascending"
                                    style="width: 208px;">
                                    <i class="fa fa-fw fa-phone text-muted hidden-md hidden-sm hidden-xs"></i>
                                    Manufacturer
                                </th>
                                <th class="sorting" tabindex="0" aria-controls="dt_basic" rowspan="1" colspan="1"
                                    aria-label="Company: activate to sort column ascending" style="width: 505px;">
                                    Type
                                </th>
                                <th data-hide="phone,tablet" class="sorting" tabindex="0" aria-controls="dt_basic"
                                    rowspan="1" colspan="1" aria-label=" Zip: activate to sort column ascending"
                                    style="width: 145px;">
                                    <i class="fa fa-fw fa-map-marker txt-color-blue hidden-md hidden-sm hidden-xs"></i>
                                    In Use
                                </th>
                                <th data-hide="phone,tablet" class="sorting" tabindex="0" aria-controls="dt_basic"
                                    rowspan="1"
                                    colspan="1" aria-label=" Date: activate to sort column ascending"
                                    style="width: 127px;">
                                    <i class="fa fa-fw fa-calendar txt-color-blue hidden-md hidden-sm hidden-xs"></i>
                                    Last update
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($meters as $meter)
                                <tr class='clickable-row'
                                    data-url=" {{ route('meter.detail', ['id' => $meter->id]) }}">

                                    <td>{{$meter->id}}</td>
                                    <td>{{$meter->serial_number}}</td>

                                    <td>{{$meter->manufacturer->name ?? ''}}</td>
                                    <td>{{$meter->meterType()->first() ?? ''}}</td>
                                    <td>
                                        @if($meter->in_use === 0)
                                            <span class="label label-danger">Not used</span>
                                        @else
                                            <span class="label label-success">In use</span>
                                        @endif

                                    </td>
                                    <td>{{$meter->updated_at->diffForHumans()}}</td>

                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endcomponent

            </article> <!-- widget end -->
        </div>
    </section>
@endsection

@section('documentReady')
    $(".clickable-row").click(function() {
    window.location = $(this).data("url");
    });
@endsection
