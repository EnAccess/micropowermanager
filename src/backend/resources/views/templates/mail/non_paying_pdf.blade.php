<html>
<head>
    <title>PDF Builder</title>
</head>
<body>
<h1>Cluster: {{$data['title']}} </h1>

<h3>This report contains customers who did not buy anything in the given period below</h3>
<h5>Covered period {{$data['start_date']}} - {{$data['end_date']}} </h5>
<h5>Customers: {{count($data['customers'])}} </h5>

<table>
    <thead>
    <tr>
        <th>Customer</th>
        <th>Mini-Grid</th>
        <th>Meter</th>
        <th>MeterType</th>
        <th>ConnectionGroup</th>
        <th>ConnectionType</th>
        <th>Tariff</th>
    </tr>
    </thead>
    <tbody class="table-body">
    @foreach($data['customers'] as $customer)
        <tr class="border-bottom">
            <td>{{$customer->name}} {{$customer->surname}}</td>
            @if(count($customer->addresses ))
                <td>{{$customer->addresses[0]->city->name}}</td>
            @else
                <td>-</td>
            @endif
            @if(count($customer->devices))

                @foreach($customer->devices as $device)

                    <td>{{$device->device->serial_number ?? '-'}}</td>
                    <td> {{$device->device->meterType ?? '-'}}</td>
                    <td> {{$device->device->connectionGroup->name ?? '-'}}</td>
                    <td> {{$device->device->connectionType->name ?? '-'}}</td>
                    <td>
                        {{$device->device->tariff->name ?? '-'}}
                        {{$device->device->tariff->total_price/100 ?? '-'}}
                    </td>

                @endforeach

            @else
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
            @endif
        </tr>
    @endforeach
    </tbody>
</table>


</body>

<style>
    .table-body {
        font-size: 12px;
    }

    tr.border-bottom td {
        border-bottom: 1px solid black;
    }
</style>
</html>
