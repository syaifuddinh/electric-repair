<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SPIL ToMS | Login</title>

    <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{asset('font-awesome/css/font-awesome.css')}}" rel="stylesheet">

    <link href="{{asset('css/animate.css')}}" rel="stylesheet">
    <link href="{{asset('css/style.css')}}" rel="stylesheet">

</head>

<body class="gray-bg skin-3">

    <div class="middle-box text-center loginscreen animated fadeInDown">
        <div>
            <div>

                <h1 class="logo-name"></h1>
                <img src="{{asset('img/spil.png')}}" style="max-width: 300px;" alt="">
                <hr>
            </div>
            <h3>Selamat Datang di SPIL ToMS</h3>
            <!-- <p>Perfectly designed and precisely prepared admin theme with over 50 pages with extra new web app views. -->
                <!--Continually expanded and constantly improved Inspinia Admin Them (IN+)-->
            </p>
            <!-- <p>Login in. To see it in action.</p> -->
            <form class="m-t" role="form" action="{{route('login')}}" method="POST">
              {{csrf_field()}}
                <div class="form-group">
                    <input type="text" class="form-control" placeholder="Email" value="{{old('email')}}" name="email" required="" autocomplete="off">
                </div>
                <div class="form-group">
                    <input type="password" class="form-control" name="password" placeholder="Password" required="" autocomplete="new-password">
                    @if ($errors->has('password'))
                        <span class="help-block">
                            <strong>{{ $errors->first('password') }}</strong>
                        </span>
                    @endif
                </div>
                @if ($errors->has('email'))
                    <span class="help-block" style="color: #b20808;">
                        <strong>{{ $errors->first('email') }}</strong>
                    </span>
                @endif
                <button type="submit" class="btn btn-rounded btn-warning block full-width m-b">Login</button>

                <!-- <a href="#"><small>Forgot password?</small></a>
                <p class="text-muted text-center"><small>Do not have an account?</small></p>
                <a class="btn btn-rounded btn-sm btn-white btn-block" href="register.html">Create an account</a> -->
            </form>
            <p class="m-t"> <small>SPIL Trucking Order Management System <br> <b>PT. SPIL &copy; 2018</b></small> </p>
        </div>
    </div>

    <!-- Mainly scripts -->
    <script src="{{asset('js/jquery-3.1.1.min.js')}}"></script>
    <script src="{{asset('js/bootstrap.min.js')}}"></script>

</body>

</html>
