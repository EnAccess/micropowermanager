<!-- each widget needs a unique ID -->
<div class="jarviswidget
@if (isset($widgetColor))
{!! html_entity_decode($widgetColor) !!}
@endif " id="{{$widgetID}}"
    @if(isset($widgetOptions))
    {!!html_entity_decode($widgetOptions)!!}
    @else
    data-widget-editbutton="false" data-widget-colorbutton="false" data-widget-deletebutton="false"
    @endif
>
    <header>
        <span class="widget-icon"><i class="fa {{$widgetIcon}}"></i></span>
        <h2>{{$widgetTitle}}</h2>
        @if(isset($headerOptions))
            {!!html_entity_decode($headerOptions)!!}
        @endif
    </header>
    <!-- widget div-->
    <div role="content">
        <!-- widget body -->
        <div class="widget-body no-padding">
            {{$slot}}
        </div> <!-- end widgetbody-->
    </div><!-- end widget div-->

</div> <!-- end widget -->
