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

            <router-view></router-view>

        </div>

    </section>
@endsection

@section('documentReady')
    $(".clickable-row").click(function() {
    window.location = $(this).data("url");
    });
@endsection
