<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SPIL TMS | Register</title>

    <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="{{asset('css/plugins/iCheck/custom.css')}}" rel="stylesheet">
    <link href="{{asset('css/animate.css')}}" rel="stylesheet">
    <link href="{{asset('css/style.css')}}" rel="stylesheet">

</head>

<body class="gray-bg">
  <noscript>
      <div style="position: fixed; top: 0px; left: 0px; z-index: 3000;
                  height: 100%; width: 100%; background-color: #FFFFFF">
          <p style="width: 25%; margin: 0 auto;margin-top: 50px; text-align: center;"><span class="fa fa-warning"></span> <br> Harap Nyalakan Javascript pada pengaturan web browser anda!</p>
      </div>
  </noscript>

    <div class="middle-box text-center loginscreen   animated fadeInDown">
        <div>
            <div>

                <h1 class="logo-name">SPIL</h1>

            </div>
            <h3>Register to SPIL TMS</h3>
            <p>Maintenance Management System</p>
            <form class="m-t" role="form" action="{{ route('register') }}" method="POST">
              {{ csrf_field() }}
                <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
                    <input type="text" name="name" class="form-control" autofocus value="{{ old('name') }}" placeholder="Name" required="">
                    @if ($errors->has('name'))
                        <span class="help-block">
                            <strong>{{ $errors->first('name') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                    <input type="email" name="email" class="form-control" placeholder="Email" required="">
                    @if ($errors->has('email'))
                        <span class="help-block">
                            <strong>{{ $errors->first('email') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                    <input type="password" name="password" class="form-control" placeholder="Password" required="">
                    @if ($errors->has('password'))
                        <span class="help-block">
                            <strong>{{ $errors->first('password') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="form-group">
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Password Confirm" required="">
                </div>
                <button type="submit" class="btn btn-rounded btn-primary block full-width m-b">Register</button>

                <p class="text-muted text-center"><small>Sudah punya akun? silahkan Login</small></p>
                <a class="btn btn-rounded btn-sm btn-white btn-block" href="{{route('login')}}">Login</a>
            </form>
            <p class="m-t"> <small>Pilarmedia Indonesia &copy; 2018</small> </p>
        </div>
    </div>

    <!-- Mainly scripts -->
    <script src="{{asset('js/jquery-3.1.1.min.js')}}"></script>
    <script src="{{asset('js/bootstrap.min.js')}}"></script>
    <!-- iCheck -->
    <script src="{{asset('js/plugins/iCheck/icheck.min.js')}}"></script>
    <script>
        $(document).ready(function(){
            $('.i-checks').iCheck({
                checkboxClass: 'icheckbox_square-green',
                radioClass: 'iradio_square-green',
            });
        });
    </script>
</body>

</html>
