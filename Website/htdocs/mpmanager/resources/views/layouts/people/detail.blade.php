@extends('layouts.app')

@section('content')

    <div class="row">
        <div class="col-xs-12 col-sm-7 col-md-7 col-lg-4">
            <h1 class="page-title txt-color-blueDark">
                <i class="fa fa-list fa fw"></i>
                Detail
                <span>> of {{$person->name}} {{$person->surname}}</span>
            </h1>
        </div>
    </div>

    <section id="widget-grid">
        <div class="row">
            <!-- widget start -->
            <article class="col-sm-10 col-xs-10 col-md-10 col-lg-10">
                @component('skeletons.widget', ['widgetID' => 'personDetail', 'widgetIcon' => 'fa-list', 'widgetTitle' => 'Person'])
                    <div class="row">
                        <div class="col-sm-2  profile-pic">
                            <img src="{{ asset('admin/img/avatars/sunny-big.png') }}" alt="">
                        </div>
                        <div class="col-sm-7">
                            <h2>{{ $person->name }} {{$person->surname}}</h2>
                            <ul class="list-unstyled">
                                <li>
                                    <p class="text-muted">
                                        <i class="fa fa-phone"></i>&nbsp;&nbsp;
                                        {{$person->addresses[0]->phone ?? '-'}}
                                    </p>
                                </li>

                                <li>
                                    <p class="text-muted">
                                        <i class="fa fa-envelope"></i>&nbsp;&nbsp;
                                        {{$person->addresses[0]->email?? '-'}}
                                    </p>
                                </li>
                                <li>
                                    <p class="text-muted">
                                        <i class="fa fa-calendar"></i>&nbsp;&nbsp;
                                        {{$person->created_at->diffForHumans()}}
                                    </p>
                                </li>

                            </ul>
                        </div>
                        <div class="col-sm-3">
                            <h2>Role{{ count($person->roleOwner) >1? 's' : '' }}</h2>
                            <ul class="list-unstyled">
                                @foreach($person->roleOwner as $roles)
                                    <li>{{ ucfirst($roles->definitions->role_name)}}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                @endcomponent
            </article>

            <article class="col-sm-2 col-xs-2 col-md-2 col-lg-2">
                @component('skeletons.widget', ['widgetID' => 'payments', 'widgetIcon' => 'fa-list', 'widgetTitle' => 'Payment Flow'])
                    <div class="margin-top-10">

                    </div>
                    <div class="col col-sm-12">
                        <div class="txt-color-blue hidden-mobile hidden-md hidden-sm" id="sparkline"></div>
                        <h5> PaymentFlow <span class="txt-color-blue" id="flow_total">$00,00</span></h5>
                    </div>
                    <div class="col col-sm-12">
                        Average Period <span class="txt-color-yellow">{{$difference}} </span>
                    </div>
                    <div class="col col-sm-12">
                        Last Payment <span
                                class="txt-color-{{(int)$lastTransactionDate > (int)$difference ? 'red': 'blue'}}">{{$lastTransactionDate}} </span>
                    </div>


                @endcomponent
            </article>

            <article class="col-sm-6 col-xs-12 col-md-6 col-lg-6">
                @component('skeletons.widget', ['widgetID' => 'lastTransactions', 'widgetIcon' => 'fa-list', 'widgetTitle' => 'Last Transactions'])
                    <table id="dt_basic" class="table table-striped table-bordered table-hover dataTable no-footer"
                            width="100%"
                            role="grid" aria-describedby="dt_basic_info" style="width: 100%;">
                        <thead>
                        <tr role="row">
                            <th>Payment Type</th>
                            <th>Sender</th>
                            <th>Amount</th>
                            <th>Paid for</th>
                            <th>Payment Service</th>
                            <th>Created At</th>
                        </tr>
                        </thead>
                        @foreach($person->transactions as $transaction)
                            <tr class='clickable-row'
                                data-url=" {{ route('transaction.detail', ['id' => $transaction->id]) }}">
                                <td>{{ucwords($transaction->payment_type)}}</td>
                                <td>{{$transaction->sender}}</td>
                                <td>{{$transaction->amount}}</td>
                                <td>{{$transaction->paidfor}}</td>
                                <td>{{ucwords($transaction->payment_service)}}</td>
                                <td>{{$transaction->created_at->diffForHumans()}}</td>
                            </tr>
                        @endforeach

                    </table>
                @endcomponent
            </article>
            <article class="col-sm-6 col-xs-12 col-md-6 col-lg-6">
                @component('skeletons.widget', ['widgetID' => 'paymentProfile', 'widgetIcon' => 'fa-list', 'widgetTitle' => 'Payment Profile',
                'headerOptions' => '
                <div class="widget-toolbar" role="menu">
                <div class="btn-group">
                    <button class="btn dropdown-toggle btn-xs btn-primary" data-toggle="dropdown" aria-expanded="false">
                        Period <i class="fa fa-caret-down"></i>
                    </button>
                    <ul class="dropdown-menu pull-right">
                        <li>
                            <a href="#/" onclick="getPaymentHistory(\'D\')">Daily</a>
                        </li>
                        <li>
                            <a href="#/" onclick="getPaymentHistory(\'W\')">Weekly</a>
                        </li>
                        <li>
                            <a href="#/" onclick="getPaymentHistory(\'M\')">Monthly</a>
                        </li>
                        <li>
                            <a href="#/" onclick="getPaymentHistory(\'Y\')">Annually</a>
                        </li>
                    </ul>
                </div>
            </div>',])

                    <div id="normal-bar-graph" class="chart no-padding"></div>


                @endcomponent
            </article>

            <article class="col-sm-8 col-xs-12 col-md-7 col-lg-7">
                @component('skeletons.widget', ['widgetID' => 'map', 'widgetIcon' => 'fa-list', 'widgetTitle' => 'Map'])
                    <div id="map_canvas2" class="google_maps"></div>
                @endcomponent
            </article>

            <article class="col-sm-4 col-xs-12 col-md-5 col-lg-5">
                @component('skeletons.widget', ['widgetID' => 'meter', 'widgetIcon' => 'fa-list', 'widgetTitle' => 'Meter',
                'headerOptions' => '
                <div class="widget-toolbar">
                    <a href="#/" class="btn btn-success">Assign</a>
                </div>'])
                    <table id="dt_basic" class="table table-striped table-bordered table-hover dataTable no-footer"
                            width="100%"
                            role="grid" aria-describedby="dt_basic_info" style="width: 100%;">
                        <thead>
                        <tr role="row">
                            <th data-hide="phone" class="sorting_asc" tabindex="0" aria-controls="dt_basic"
                                rowspan="1"
                                colspan="1"
                                aria-sort="ascending" aria-label="ID: activate to sort column descending"
                                style="width: 61px;">Serial Number
                            </th>
                            <th data-hide="phone" class="sorting_asc" tabindex="0" aria-controls="dt_basic"
                                rowspan="1"
                                colspan="1"
                                aria-sort="ascending" aria-label="ID: activate to sort column descending"
                                style="width: 61px;">Phase(s)
                            </th>
                            <th data-hide="phone" class="sorting_asc" tabindex="0" aria-controls="dt_basic"
                                rowspan="1"
                                colspan="1"
                                aria-sort="ascending" aria-label="ID: activate to sort column descending"
                                style="width: 61px;">Max Current(A)
                            </th>
                        </tr>
                        @foreach($person->meters as $meter)
                            <tr>
                                <td>{{$meter->meter->serial_number}}</td>
                                <td>{{$meter->meter->meterType->phase}}</td>
                                <td>{{$meter->meter->meterType->max_current}}</td>
                            </tr>
                        @endforeach
                    </table>

                @endcomponent
            </article>

            <article class="col-sm-4 col-xs-12 col-md-5 col-lg-5">
                @component('skeletons.widget', ['widgetID' => 'tickets', 'widgetIcon' => 'fa-list', 'widgetTitle' => 'Tickets',
                'headerOptions' => '
                <div class="widget-toolbar">
                    <a href="#/" class="btn btn-success" data-toggle="modal" data-target="#myModal">New</a>
                </div>
                <ul class="nav  nav-tabs pull-right in" id="ticketTab">
                    <li class="active">
                        <a data-toggle="tab" href="#all">
                            <i class="fa fa-clock-o"></i>
                            <span class="hidden-mobile hidden-tablet">All</span>
                        </a>
                    </li>
                    <li>
                        <a data-toggle="tab" href="#open">
                            <i class="fa fa-clock-o"></i>
                            <span class="hidden-mobile hidden-tablet">Open</span>
                        </a>
                    </li>
                    <li>
                        <a data-toggle="tab" href="#closed">
                            <i class="fa fa-clock-o"></i>
                            <span class="hidden-mobile hidden-tablet">Closed</span>
                        </a>
                    </li>
                </ul>',
                ])
                    <div id="myTabContent" class="tab-content">
                        <div class="tab-pane fade active in padding-10 no-padding-bottom" id="all">
                            s1
                        </div>
                        <div class="tab-pane fade in padding-10 no-padding-bottom" id="open">
                            s2
                        </div>
                        <div class="tab-pane fade in padding-10 no-padding-bottom" id="closed">
                            s3
                        </div>
                    </div>
                @endcomponent
            </article>
        </div>
    </section>
    @include('layouts.tickets.newModal')
@endsection


@section('documentReady')
    getPaymentHistory();
    getPaymentFlow();
    $(".clickable-row").click(function() {
    window.location = $(this).data("url");
    });


    // START AND FINISH DATE
    $('#startdate').datepicker({
    dateFormat : 'dd.mm.yy',
    prevText : '<i class="fa fa-chevron-left"></i>',
    nextText : '<i class="fa fa-chevron-right"></i>',

    });

    //initialize inputs after the document loaded completely
    initInputs();
    //get a list of registered trello users
    getTrelloUsers();

    ticketForm=  $('#ticketForm');

    ticketForm.submit(function(e){
    e.preventDefault();
    console.log("form submitted");
    });


@endsection

@section('footer-scripts')
    <script async defer
            src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCmztMQfl2H7bFk260Dl3oBAKjIVO7Btlo&callback=initMap"
            type="text/javascript">
    </script>
    <!-- Morris Chart Dependencies -->
    <script src="{{asset('admin/js/plugin/morris/raphael.min.js')}}"></script>
    <script src="{{asset('admin/js/plugin/morris/morris.min.js')}}"></script>
    <script src="https://omnipotent.net/jquery.sparkline/2.1.2/jquery.sparkline.min.js"></script>

    <script>
        function getPaymentHistory(flow = 'W') {
            let paymentData = [];
            $.getJSON("/api/paymenthistories/{{$person->id}}/payments/" + flow, function (data) {
                let items = [];
                let values;

                $.each(data, function (key, val) {
                    values = {};
                    values['x'] = key;
                    $.each(val, function (vkey, vval) {
                        values[vkey] = vval;
                    });
                    items.push(values);
                });


                initPaymentFlowChart(items);
            });
        }

        function getPaymentFlow() {
            let paymentData = [];
            $.getJSON("/api/paymenthistories/{{$person->id}}/flow/", function (data) {


                initPaymentFlow(data);
            });
        }

        function sum(pArray) {
            pArray = pArray.reduce(function (a, b) {
                return parseInt(a) + parseInt(b);
            }, 0);

            return pArray;
        }

        function initPaymentFlow(values) {
            $('#flow_total').html(sum(values) + 'TZS');

            // Draw a sparkline for the #sparkline element
            $('#sparkline').sparkline(values, {
                type: "bar",
                barWidth: 12,
                barColor: '#57889c',
                // Map the offset in the list of values to a name to use in the tooltip
                tooltipFormat: '@{{offset:offset}} @{{value}}',
                tooltipValueLookups: {
                    'offset': {
                        0: 'Jan',
                        1: 'Feb',
                        2: 'Mar',
                        3: 'Apr',
                        4: 'May',
                        5: 'Jun',
                        6: 'Jul',
                        7: 'Aug',
                        8: 'Sep',
                        9: 'Oct',
                        10: 'Nov',
                        11: 'Dec',


                    }
                },
            });
        }

        function initPaymentFlowChart(chartData) {
            // Use Morris.Bar
            if ($('#normal-bar-graph').length) {
                $('#normal-bar-graph').html('');
                var bar = Morris.Bar({
                    element: 'normal-bar-graph',
                    resize: true,
                    redraw: true,
                    data: chartData,
                    xkey: 'x',
                    ykeys: ['energy', 'access rate', 'deferred_payments'],
                    labels: ['Energy', 'Access Rate', 'Deferred Payments']
                });

            }

            $('#normal-bar-graph').resize(function () {
                bar.redraw();
            });
        }

        function initMap() {

            var mapOptions = {
                center: new google.maps.LatLng(0.0, 0.0),
                zoom: 8,
            };

            map = new google.maps.Map(document.getElementById('map_canvas2'), mapOptions);

            // Setup skin for the map
            map.mapTypes.set('colorful_style',
                new google.maps.StyledMapType({

                    "featureType": "landscape",
                    "stylers": [{
                        "visibility": "off"
                    }]
                }, {
                    name: "Colorful"
                }));
            map.setMapTypeId('colorful_style');


            //add markers
                    @foreach($person->meters as $meter )
            var marker = new google.maps.Marker({
                    position: {
                        lat: {{ $meter->latLon()[0] }},
                        lng: {{ $meter->latLon()[1] }},
                    },
                    map: map,
                    title: 'Hello World!'
                });

            @endforeach

        }

        let ticketForm;
        let ticketTitle;
        let ticketDescription;
        let ticketPriority;
        let ticketAssignedTo;
        let ticketDueDate;

        function setToday() {
            let date = new Date();
            let year = date.getUTCFullYear();
            let month = (date.getUTCMonth() + 1) < 10 ? '0' + (date.getUTCMonth() + 1) : (date.getUTCMonth() + 1);
            let day = (date.getUTCDate()) < 10 ? '0' + (date.getUTCDate()) : (date.getUTCDate());
            ticketDueDate.attr('value', day + '.' + month + '.' + year);
        }

        function initInputs() {
            ticketTitle = $('#ticketTitle');
            ticketDescription = $('#ticketDescription');
            ticketPriority = $('#ticketPriority');
            ticketAssignedTo = $('#ticketAssignedTo');
            ticketDueDate = $('#ticketDueDate');
        }


        function getTrelloUsers() {
            $.getJSON("/tickets/api/users/", function (data) {
                $.each(data.data, function (key, val) {
                    ticketAssignedTo.append('<option value="' + val.trello_id + '">' + val.user_name + '</option>');
                });
            });

        }

        function createTicket() {
            let title = ticketTitle.val();
            let desc = ticketDescription.val();
            let priority = ticketPriority.val();
            let assignedTo = ticketAssignedTo.val();
            let dueDate = ticketDueDate.val();

            let formData = {
                sko

            };

            $.ajax(
                {
                    url: '/tickets/api/ticket',
                    type: 'POST',
                    data: JSON.stringify(formData),
                    contentType: 'application/json',

                }
            ).done(function () {

            }).fail(function () {

            });

        }
    </script>
@endsection

