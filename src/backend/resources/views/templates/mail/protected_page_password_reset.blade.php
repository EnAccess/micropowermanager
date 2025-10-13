<html>
<head>
    <title>{{$title}}</title>
</head>

<body>
    <b>Reset Protected Pages Password</b>
    <p>
        Dear {{$userName}}, you can reset your Protected Pages Password by clicking the link below:
    </p>
    <p>
        <a href="{{$resetUrl}}">Reset Protected Pages Password</a>
    </p>
    <p>
        This link will expire on {{$expiresAt}}. If you don't reset your password by then, you'll need to request a new reset link.
    </p>
    <p>
        If you didn't request this password reset, please ignore this email. Your Protected Pages Password will remain unchanged.
    </p>
    <p>{{$companyName}}</p>

    <small>This is an automatic mail please don't reply to it.</small>

</body>
</html>
