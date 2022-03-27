<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>INDRA'S ELECTRIC REPAIR</title>

    <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{asset('font-awesome/css/font-awesome.css')}}" rel="stylesheet">

    <link href="{{asset('css/plugins/iCheck/custom.css')}}" rel="stylesheet">
    <link href="{{asset('css/animate.css')}}" rel="stylesheet">
    <link href="{{asset('css/style.css')}}" rel="stylesheet">
</head>

<body class="gray-bg">

    <div class="middle-box text-center loginscreen animated fadeInDown">
        <div>
            <div>

                <h1 class="logo-name"></h1>
                <hr>
            </div>
            <h3>INDRA'S ELECTRIC REPAIR</h3>
            </p>
            <!-- <p>Login in. To see it in action.</p> -->
            <form class="m-t" role="form" action="{{route('login')}}" method="POST">
              {{csrf_field()}}
                <div class="form-group">
                    <input type="text" class="form-control" placeholder="Email" value="{{old('email')}}" name="email" required="" autofocus>
                </div>
                <div class="form-group">
                    <input type="password" class="form-control" name="password" placeholder="Password" required="">
                    @if ($errors->has('password'))
                        <span class="help-block">
                            <strong>{{ $errors->first('password') }}</strong>
                        </span>
                    @endif
                </div>
                <!-- <div class="form-group text-left">
                  <input type="checkbox" class="icheck" name="remember" checked> <strong>Ingat Saya!</strong>
                </div> -->
                @if ($errors->has('email'))
                    <span class="help-block" style="color: #b20808;">
                        <strong>{{ $errors->first('email') }}</strong>
                    </span>
                @endif
                <button type="submit" class="btn btn-info block full-width m-b">Login</button>
                <!-- <img src="{{asset('img/solog.png')}}" class="solog" style="width: 300px;" alt=""> -->

                <!-- <a href="#"><small>Forgot password?</small></a>
                <p class="text-muted text-center"><small>Do not have an account?</small></p>
                <a class="btn btn-rounded btn-sm btn-white btn-block" href="register.html">Create an account</a> -->
            </form>
        </div>
    </div>

    <!-- Mainly scripts -->
    <script src="{{asset('js/jquery-3.1.1.min.js')}}"></script>
    <script src="{{asset('js/bootstrap.min.js')}}"></script>
    <script src="{{asset('js/plugins/iCheck/icheck.min.js')}}"></script>
    <script type="text/javascript">
      $('.icheck').iCheck({
        checkboxClass: 'icheckbox_square-green',
        radioClass: 'iradio_square-green',
      })
    </script>
</body>

</html>
