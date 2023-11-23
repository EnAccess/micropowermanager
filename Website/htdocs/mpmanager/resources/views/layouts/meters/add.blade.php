@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-xs-12 col-sm-7 col-md-7 col-lg-4">
            <h1 class="page-title txt-color-blueDark">
                <i class="fa fa-plus fa fw"></i>
                New
                <span>> type of {{$title}}</span>
            </h1>
        </div>
    </div>


    <section id="widget-grid">
        <div class="row">
            <!-- widget start -->
            <article class="col-sm-12 col-xs-12 col-md-12 col-lg-12">
                @component('skeletons.widget', ['widgetID' => 'new-meter', 'widgetIcon'=>'fa-plus', 'widgetTitle' => 'New Meter'])
                    <form id="order-form" class="smart-form" novalidate="novalidate" method="POST"
                            action={{route('meters.new')}}>
                        @csrf
                        <header>
                            Meter
                        </header>

                        <fieldset>
                            <div class="row">
                                <section class="col col-sm-12">
                                    <label class="input"> <i class="icon-append fa fa-user"></i>
                                        <input type="text" name="serial_number" placeholder="Serial Number"
                                                value="{{ old('serial_number') }}">
                                    </label>
                                    @if($errors->has('serial_number'))
                                        <span class="invalid-feedback">
                                        <strong>{{ $errors->first('serial_number') }}</strong>
                                    </span>
                                    @endif

                                </section>
                            </div>

                            <div class="row">
                                <section class="col col-6">
                                    <label class="select">
                                        <select name="meter_type">
                                            <option value="0" selected="" disabled="">Meter Type</option>
                                            @foreach($meterTypes as $meterType)
                                                @if($meterType->id == old('meter_type'))
                                                    <option
                                                        value="{{ $meterType->id }}" selected> {{ $meterType }}</option>
                                                @else
                                                    <option
                                                        value="{{ $meterType->id }}"> {{ $meterType }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <i></i>
                                        @if($errors->has('meter_type'))
                                            <span class="invalid-feedback">
                                        <strong>{{ $errors->first('meter_type') }}</strong>
                                    </span>
                                        @endif
                                    </label>
                                </section>
                                <section class="col col-6">
                                    <label class="select">
                                        <select name="manufacturer">
                                            <option value="0" selected="" disabled="">Manufacturer</option>
                                            @foreach($manufacturers as $manufacturer)
                                                @if( old('manufacturer') == $manufacturer->id)
                                                    <option
                                                        value="{{ $manufacturer->id }}"
                                                        selected> {{ $manufacturer->name }}</option>
                                                @else
                                                    <option
                                                        value="{{ $manufacturer->id }}"> {{ $manufacturer->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <i></i>
                                        @if($errors->has('manufacturer'))
                                            <span class="invalid-feedback">
                                        <strong>{{ $errors->first('manufacturer') }}</strong>
                                    </span>
                                        @endif
                                    </label>
                                </section>
                            </div>
                        </fieldset>

                        <footer>
                            <button type="submit" class="btn btn-primary">
                                Save new Meter
                            </button>
                        </footer>
                    </form>
                @endcomponent
            </article>
        </div>
    </section>

@endsection
