@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-xs-12 col-sm-7 col-md-7 col-lg-4">
            <h1 class="page-title txt-color-blueDark">
                <i class="fa fa-list fa fw"></i>
                Transaction Detail
                <span>> of {{$title}}</span>
            </h1>
        </div>
    </div>

    <section id="widget-grid">
        <div class="row">
            <!-- widget start -->
            <article class="col-sm-12 col-xs-12 col-md-6 col-lg-6">
                @component('skeletons.widget', ['widgetID' => 'transaction.details', 'widgetIcon' => 'fa-list', 'widgetTitle' => "Transaction ($transaction->id)"])
                    <div class="margin-top-10"></div>

                    <div class="col col-md-4 col-sm-6">
                        Sender :
                    </div>
                    <div class="col col-md-8 col-sm-6">
                        <i class="fa fa-phone"></i> {{$transaction->sender}}
                    </div>




                    <div class="col col-md-4 col-sm-6">
                        Type :
                    </div>
                    <div class="col col-md-8 col-sm-6">
                        @if($transaction->type === 'energy')
                            <i class="fa fa-bolt"></i>
                        @elseif ($transaction->type === 'deferred_payment')
                            <i class="fa fa-dollar"></i>
                        @endif
                        {{ucfirst($transaction->type)}}
                    </div>


                    <div class="col col-md-4 col-sm-6">
                        Sent :

                    </div>
                    <div class="col col-md-8 col-sm-6">
                        <i class="fa fa-calendar"></i>
                        {{$transaction->created_at->diffForHumans()}}, ({{$transaction->created_at}})
                    </div>


                    <div class="col col-md-4 col-sm-6">
                        Amount :
                    </div>
                    <div class="col col-md-8 col-sm-6">
                        <i class="fa fa-dollar"></i>
                        {{$transaction->amount}}
                    </div>

                @endcomponent
            </article>
            @if( $transaction->type === 'energy')
                <article class="col-sm-12 col-xs-12 col-md-6 col-lg-6">
                    @component('skeletons.widget', ['widgetID' => 'transaction.processes', 'widgetIcon' => 'fa-bolt', 'widgetTitle' => "Processes for ($transaction->id)"])
                        <div class="margin-top-10"></div>
                        @if($transaction->originalTransaction->status !== -1)
                            <div class="col col-md-4 col-sm-6">
                                <i class="fa fa-code"></i> Token Generation :
                            </div>
                            <div class="col col-sm-6 col-md-8">
                                @if( $transaction->token === null)
                                    <i class="fa fa-times text-danger"></i> Failed.
                                    <button class="btn btn-primary btn-sm"> Generate token & Send sms</button>
                                @else
                                    <i class="fa fa-check text-success"></i> (
                                    <small>{{ $transaction->token->token }}</small>)
                                @endif
                            </div>

                            <div class="col col-md-4 col-sm-6">
                                <i class="fa fa-envelope"></i> Sms:
                            </div>
                            <div class="col col-sm-6 col-md-8">
                                @if( $transaction->sms === null)
                                    <i class="fa fa-times text-danger"></i> Failed.
                                    <button
                                        class="btn btn-primary btn-sm {{$transaction->token === null ? 'hidden': ''}}">
                                        Resend sms
                                    </button>
                                @else
                                    <i class="fa fa-check text-success"></i> (
                                    <small>{{ $transaction->sms->body}}</small>)
                                @endif
                            </div>

                        @else
                            <div class="col col-sm-12">
                                <div class="alert-danger">
                                    <h1 class="padding-10">This transaction was cancelled. Explanation(s) are below</h1>
                                </div>
                                @foreach($transaction->originalTransaction->conflicts as $conflict)
                                    <li>{{$conflict->state}}</li>
                                @endforeach
                                <div class="margin-top-10"></div>
                            </div>
                        @endif
                    @endcomponent
                </article>
                @if($transaction->token !== null)
                    <article class="col-sm-12 col-xs-12 col-md-6 col-lg-6">
                        @component('skeletons.widget', ['widgetID' => 'transaction.meter', 'widgetIcon' => 'fa-bolt', 'widgetTitle' => "Affected Meter by $transaction->id"])
                            <div class="row">
                                <div class="col col-sm-6 col-md-4">
                                    Meter Serial:
                                </div>
                                <div class="col col-sm-6 col-md-8">
                                    <a href="{{route('meter.detail', ['id' => $transaction->tokken->meter->serial_number ?? 0])}}">{{ $transaction->token->meter->serial_number ?? '-'}}</a>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col col-sm-6 col-md-4">
                                    Meter Owner:
                                </div>
                                <div class="col col-sm-6 col-md-8">
                                    <a href="{{ route('person.detail', ['id' =>  $transaction->token->meter->meterParameter->owner()->first()->id]) }}">{{ $transaction->token->meter->meterParameter->owner()->first()}}</a>
                                </div>

                        @endcomponent
                    </article>
                @endif

            @endif


        </div>
    </section>

@endsection
