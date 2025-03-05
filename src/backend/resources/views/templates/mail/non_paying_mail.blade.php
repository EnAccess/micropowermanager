<html>
<head>
    <title>Mail template</title>
</head>
<body>
Dear <strong>{{$manager->name}}</strong>,<br> please find in the attachment a list of customers
for Cluster(<stong>{{$cluster_name}}</stong>) who did not had any transactions in previous month ({{$period}}).

This is an automatic mail please don't reply to it.

<br>
<small>MicroPowerManager</small>

</body>

</html>
