<html>
<head>
    <title>{{$title}}</title>
</head>

<body>
    <b>Password reset</b>
    <p>
        Dear {{$userName}},
    </p>
    <p>
        We received a request to reset your password. If you made this request, please click the link below to set a new password:
    </p>
    <p>
        <a href="{{$resetUrl}}">Reset your password</a>
    </p>
    <p>
        If you did not request a password reset, you can safely ignore this email. Your password will not be changed.
    </p>
    <p>MicroPowerManager</p>

    <small>This is an automatic mail please don't reply to it.</small>

</body>
</html>


