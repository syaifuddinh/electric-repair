<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>404 Not Found</title>

    <link href="{{asset('css/bootstrap-3.3.7.min.css')}}" rel="stylesheet">
    <link href="{{asset('font-awesome/css/font-awesome.css')}}" rel="stylesheet">

    <link href="{{asset('css/animate.css')}}" rel="stylesheet">
    <link href="{{asset('css/style.css')}}" rel="stylesheet">

</head>

<body class="gray-bg">


    <div class="middle-box text-center animated fadeInDown">
        <h1>404</h1>
        <h3 class="font-bold">Halaman tidak ditemukan!</h3>

        <div class="error-desc">
            <!-- Sorry, but the page you are looking for has note been found. Try checking the URL for error, then hit the refresh button on your browser or try found something else in our app. -->
            Mohon maaf! halaman yang anda minta tidak dapat ditampilkan.
            <form class="form-inline m-t" role="form">
                <!-- <button type="submit" class="btn btn-primary">Search</button> -->
                <a href="{{url('/')}}" class="btn btn-primary">Kembali</a>
            </form>

        </div>
    </div>

    <!-- Mainly scripts -->
    <script src="{{asset('js/jquery-3.1.1.min.js')}}"></script>
    <script src="{{asset('js/bootstrap.min.js')}}"></script>
</html>
