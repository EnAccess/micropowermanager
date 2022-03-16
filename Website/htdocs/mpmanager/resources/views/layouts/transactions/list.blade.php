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
                                <th data-class="expand" class="expand sorting" tabindex="0" aria-controls="dt_basic"
                                    rowspan="1"
                                    colspan="1" aria-label=" Name: activate to sort column ascending"
                                    style="width: 141px;">
                                    <i class="fa fa-fw fa-user text-muted hidden-md hidden-sm hidden-xs"></i> Service

                                </th>
                                <th data-hide="phone" class="sorting" tabindex="0" aria-controls="dt_basic"
                                    rowspan="1" colspan="1" aria-label=" Phone: activate to sort column ascending"
                                    style="width: 208px;">
                                    <i class="fa fa-fw fa-phone text-muted hidden-md hidden-sm hidden-xs"></i>
                                    Sender
                                </th>
                                <th class="sorting" tabindex="0" aria-controls="dt_basic" rowspan="1" colspan="1"
                                    aria-label="Company: activate to sort column ascending" style="width: 505px;">
                                    Amount
                                </th>
                                <th class="sorting" tabindex="0" aria-controls="dt_basic" rowspan="1" colspan="1"
                                    aria-label="Company: activate to sort column ascending" style="width: 505px;">
                                    Type
                                </th>

                                <th data-hide="phone,tablet" class="sorting" tabindex="0" aria-controls="dt_basic"
                                    rowspan="1"
                                    colspan="1" aria-label=" Date: activate to sort column ascending"
                                    style="width: 127px;">
                                    <i class="fa fa-fw fa-calendar txt-color-blue hidden-md hidden-sm hidden-xs"></i>
                                    Message
                                </th>
                                <th data-hide="phone,tablet" class="sorting" tabindex="0" aria-controls="dt_basic"
                                    rowspan="1"
                                    colspan="1" aria-label=" Date: activate to sort column ascending"
                                    style="width: 127px;">
                                    <i class="fa fa-fw fa-calendar txt-color-blue hidden-md hidden-sm hidden-xs"></i>
                                    Sent Date
                                </th>
                                <th data-hide="phone,tablet" class="sorting" tabindex="0" aria-controls="dt_basic"
                                    rowspan="1"
                                    colspan="1" aria-label=" Date: activate to sort column ascending"
                                    style="width: 127px;">
                                    <i class="fa fa-fw fa-calendar txt-color-blue hidden-md hidden-sm hidden-xs"></i>
                                    Confirmation/Cancellation to third party
                                </th>
                                <th data-hide="phone,tablet" class="sorting" tabindex="0" aria-controls="dt_basic"
                                    rowspan="1"
                                    colspan="1" aria-label=" Date: activate to sort column ascending"
                                    style="width: 127px;">
                                    <i class="fa fa-fw fa-calendar txt-color-blue hidden-md hidden-sm hidden-xs"></i>
                                    Status
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($transactions as $transaction)
                                <tr class='clickable-row'
                                    data-url=" {{ route('transaction.detail', ['id' => $transaction->id]) }}">

                                    <td>{{$transaction->original_transaction_type}}</td>
                                    <td>{{$transaction->sender}}</td>
                                    <td>{{$transaction->amount}}</td>
                                    <td>{{$transaction->type}}</td>
                                    <td>{{$transaction->message}}</td>
                                    <td>{{$transaction->created_at->diffForHumans()}}</td>
                                    <td>{{$transaction->updated_at->diffForHumans()}}</td>

                                    <td>
                                        @if($transaction->originalTransaction === null )
                                            <span class="label label-danger"> <b>Base transaction not found</b></span>
                                        @else
                                            @if($transaction->originalTransaction->status === 0 ?? false)
                                                <span class="label label-warning">Not confirmed yet</span>
                                            @elseif($transaction->originalTransaction->status === -1)
                                                <span class="label label-danger">Cancelled</span>
                                            @else
                                                <span class="label label-success">Confirmed</span>
                                            @endif
                                        @endif


                                    </td>


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

