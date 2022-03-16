@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-xs-12 col-sm-7 col-md-7 col-lg-4">
            <h1 class="page-title txt-color-blueDark">
                <i class="fa fa-list fa fw"></i>
                List
                <span>> of &nbsp;{{ $title ?? ''}}</span>
            </h1>
        </div>
    </div>

    <section id="widget-grid">
        <div class="row">
            <!-- widget start -->
            <article class="col-sm-12 col-xs-12 col-md-12 col-lg-12">
                @component('skeletons.widget', ['widgetID' => 'peopleList', 'widgetIcon' => 'fa-list', 'widgetTitle' => 'People'])
                    <div id="dt_basic_wrapper" class="dataTables_wrapper form-inline dt-bootstrap no-footer">
                        <div class="dt-toolbar">
                            <div class="col-xs-12 col-sm-6">
                                <div id="dt_basic_filter" class="dataTables_filter">
                                    <label>
                                <span class="input-group-addon">
                                    <i class="glyphicon glyphicon-search"></i>
                                </span>
                                        <input type="search" class="form-control" placeholder=""
                                               aria-controls="dt_basic">
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xs-12 hidden-xs">
                                <div class="dataTables_length" id="dt_basic_length">
                                    <label>Show
                                        <select name="dt_basic_length" aria-controls="dt_basic"
                                                class="form-control input-sm">
                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select> entries</label>
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
                                    <i class="fa fa-fw fa-user text-muted hidden-md hidden-sm hidden-xs"></i> Name
                                </th>
                                <th data-hide="phone" class="sorting" tabindex="0" aria-controls="dt_basic"
                                    rowspan="1" colspan="1" aria-label=" Phone: activate to sort column ascending"
                                    style="width: 208px;">
                                    <i class="fa fa-fw fa-phone text-muted hidden-md hidden-sm hidden-xs"></i> Phone
                                </th>
                                <th class="sorting" tabindex="0" aria-controls="dt_basic" rowspan="1" colspan="1"
                                    aria-label="Company: activate to sort column ascending" style="width: 505px;">
                                    Company
                                </th>
                                <th data-hide="phone,tablet" class="sorting" tabindex="0" aria-controls="dt_basic"
                                    rowspan="1" colspan="1" aria-label=" Zip: activate to sort column ascending"
                                    style="width: 145px;">
                                    <i class="fa fa-fw fa-map-marker txt-color-blue hidden-md hidden-sm hidden-xs"></i>
                                    Zip
                                </th>
                                <th data-hide="phone,tablet" class="sorting" tabindex="0" aria-controls="dt_basic"
                                    rowspan="1" colspan="1" aria-label="City: activate to sort column ascending"
                                    style="width: 288px;">City
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
                            @foreach($list as $person)
                                <tr>
                                    <td>{{$person->id}}</td>
                                    <td>{{$person->name}}  {{$person->surname}}</td>
                                    <td>{{$person->addresses()->first()->phone}}</td>
                                    <td>
                                        @can('edit-client')
                                            <a href="{{ route('person-edit', ['id' =>  $person->id]) }}"
                                               class="btn btn-xs btn-default"><i class="fa fa-pencil"></i>
                                            </a>
                                        @endcan
                                        @can('view-client')
                                            <a href="{{ url('/people', [$person->id]) }}"
                                               class="btn btn-xs btn-default"><i class="fa fa-eye"></i>
                                            </a>
                                        @endcan
                                        @can('delete-client')
                                            <button class="btn btn-xs btn-default" data-original-title="Cancel"
                                                    onclick="jQuery('#jqgrid').restoreRow('1');"><i
                                                    class="fa fa-times"></i>
                                            </button>
                                        @endcan
                                    </td>
                                    <td></td>
                                    <td>{{$person->addresses()->first()->city()->first()->name}}</td>
                                    <td>{{$person->updated_at->diffForHumans()}}</td>
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
