<!DOCTYPE html>
<html ng-app="app">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BCSI | MAP SHIPMENT</title>

    <link href="{{asset('css/plugins/chosen/bootstrap-chosen.css')}}" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link href="{{asset('font-awesome/css/font-awesome.css')}}" rel="stylesheet">
    <link href="{{asset('css/animate.css')}}" rel="stylesheet">
    <link href="{{asset('css/style.css')}}" rel="stylesheet">
    <link href="{{asset('css/plugins/iCheck/custom.css')}}" rel="stylesheet">
    <link href="{{asset('css/plugins/chosen/bootstrap-chosen.css')}}" rel="stylesheet">
    <!-- <link href="{{asset('gantt/jquery.stacked-gantt.css')}}" rel="stylesheet"> -->
    <link href="{{asset('js/fullcalendar-3.9.0/fullcalendar.min.css')}}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@latest/dist/simplebar.css">
    <script src="https://cdn.jsdelivr.net/npm/simplebar@latest/dist/simplebar.js"></script>
</head>

<body class="top-navigation" data-simplebar>
    <style media="screen">
      #map {
        width: 100%;
        height: 100%;
        display: block;
        position: relative;
      }

      #overmap-1 {
        position: absolute;
        top: 150px;
        left: 10px;
        width: 20%;
        z-index: 5;
        background-color: #fff;
        padding: 5px;
        box-shadow: 1px 2px 4px rgba(0, 0, 0, .5);
        font-family: 'Roboto','sans-serif';
        line-height: 20px;
        padding-left: 10px;
        opacity: 0.9;
      }

      #overmap-2 {
        position: absolute;
        top: 60px;
        left: 250px;
        width: 150px;
        height: 50px;
        z-index: 5;
        background-color: #fff;
        padding: 5px;
        box-shadow: 1px 2px 4px rgba(0, 0, 0, .5);
        font-family: 'Roboto','sans-serif';
        line-height: 20px;
        padding-left: 10px;
        opacity: 0.9;
      }

      #overmap-3 {
        position: absolute;
        top: 60px;
        left: 250px;
        width: 500px;
        height: 42px;
        z-index: 5;
        background-color: #fff;
        padding: 5px;
        box-shadow: 1px 2px 4px rgba(0, 0, 0, .5);
        font-family: 'Roboto','sans-serif';
        line-height: 20px;
        padding-left: 10px;
        opacity: 0.9;
      }

      #overmap-4 {
        position: absolute;
        top: 60px;
        right: 10px;
        width: 300px;
        height: auto;
        z-index: 5;
        background-color: #fff;
        padding: 5px;
        box-shadow: 1px 2px 4px rgba(0, 0, 0, .5);
        font-family: 'Roboto','sans-serif';
        line-height: 20px;
        padding-left: 10px;
        opacity: 0.9;
      }

      .labels {
        background-color: rgba(0, 0, 0, 0.5);
        border-radius: 4px;
        color: white;
        padding: 4px;
      }

      .tabelbaru td{
          font-size: 11px;
      }
      .tabelbaru thead th {
          font-size: 11px; text-align: center;
      }
      .tabelkendaraan td{
          font-size: 10px;
      }
      .tabelkendaraan tr{
          cursor: pointer;
      }
      .tabelkendaraan thead th {
          background-color: #BFBFBF;  font-size: 11px; text-align: center;
      }

      table.table-small thead tr th {
        font-size: 12px;
      }

      table.table-small tbody tr td {
        font-size: 11px;
      }

      .api {
          border: 1px solid #ddd;
          border-radius: 4px;
        }

      .example-4, .example-5 {
          height: 400px;
        }

      .split p {
          padding: 2px;
        }

      .split {
          -webkit-box-sizing: border-box;
             -moz-box-sizing: border-box;
                  box-sizing: border-box;
                  overflow-y: auto;
                  overflow-x: hidden;
        }

      .gutter {
          background-color: #eee;
          background-repeat: no-repeat;
          background-position: 50%;
        }

      .chosen-container {
        width: 100% !important;
      }

      .gutter.gutter-horizontal {
          background-image: url('assets/global/plugins/split/grips/vertical.png');
          cursor: ew-resize;
        }

      .gutter.gutter-vertical {
          background-image: url("{{asset('img/horizontal_gutter.png')}}");
          cursor: ns-resize;
        }

      .split.split-horizontal, .gutter.gutter-horizontal {
          height: 100%;
          float: left;
        }
      table.table-borderless tbody tr td {
          border: none;
          padding: 4px;
          font-size: 13px;
        }

    </style>
    <div id="wrapper" ng-controller="mapController">
      <div id="" class="gray-bg">
        <div class="row border-bottom white-bg">
          <nav class="navbar navbar-static-top" role="navigation">
            <div class="navbar-header">
                <button aria-controls="navbar" aria-expanded="false" data-target="#navbar" data-toggle="collapse" class="navbar-toggle collapsed" type="button">
                    <i class="fa fa-reorder"></i>
                </button>
                <a href="{{url('/')}}" class="navbar-brand"> BCSI SHIPMENT</a>
            </div>
            <div class="navbar-collapse collapse" id="navbar">
              {{--
                <ul class="nav navbar-nav">
                  <li>
                    <a href="{{url('shipment/timeline_chart')}}"><i class="fa fa-clock-o"></i> Timeline</a>
                  </li>
                </ul>
                <ul class="nav navbar-nav">
                    <li class="dropdown">
                        <a aria-expanded="false" role="button" href="#" class="dropdown-toggle" data-toggle="dropdown"> Vehicles <span class="caret"></span></a>
                        <ul role="menu" class="dropdown-menu">
                            <li><a href="">Vehicle List</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a aria-expanded="false" role="button" href="#" class="dropdown-toggle" data-toggle="dropdown"> Jobs <span class="caret"></span></a>
                        <ul role="menu" class="dropdown-menu">
                            <li><a href="">Port Job</a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="nav navbar-top-links navbar-right">
                    <li>
                        <a href="login.html">
                            <i class="fa fa-sign-out"></i> Log out
                        </a>
                    </li>
                </ul>
                --}}
            </div>
        </nav>
        </div>
        <div class="wrapper wrapper-content" style="padding: 0px; overflow: hidden;">
          <div class="api" style="height: 92vh;">
            <div id="split1" class="split split-vertical">
              <div id="map">

              </div>

              <div id="overmap-1">
                <strong>DRIVER AKTIF HARI INI</strong> <br>
                <table class="table table-borderless">
                  <tbody>
                    <tr>
                      <td style="width:80%;">Driver Online</td>
                      <td>: <span ng-bind="listDriver.online" class="text-bold"></td>
                    </tr>
                  </tbody>
                </table>
                <b>PENGIRIMAN HARI INI</b> <br>
                <table class="table table-borderless">
                  <tbody>
                    <tr>
                      <td style="width:80%;">Total Job</td>
                      <td>: <span ng-bind="listJob.total_job" class="text-bold"></span></td>
                    </tr>
                    <tr>
                      <td>Diterima Driver / On Progress</td>
                      <td>: <span ng-bind="listJob.progress" class="text-bold"></span></td>
                    </tr>
                    <tr>
                      <td>Selesai</td>
                      <td>: <span ng-bind="listJob.selesai" class="text-bold"></span></td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div id="overmap-3">
                <div class="row">
                  <div class="col-md-10" style="padding-left: 10px; padding-right: 2px;">
                    <select class="form-control" data-placeholder-text-single="'Cari Driver'" chosen allow-single-deselect="true" ng-model="searchData.driver_id" ng-options="s.id as s.name for s in drivers">
                      <option value=""></option>
                    </select>
                  </div>
                  <div class="col-md-2" style="padding-left: 5px;">
                    <button class="btn btn-primary btn-sm" ng-click="findDriver()" type="button" >Cari</button>
                  </div>
                </div>
              </div>

              <!-- <div id="overmap-4">
                <strong>Keterangan Peta</strong> <br>
                <img src="{{asset('img/truck_black.png')}}" style="height: 24px;" alt=""> : Mesin Mati <br>
                <img src="{{asset('img/truck_green.png')}}" style="height: 24px;" alt=""> : Idle <br>
                <img src="{{asset('img/truck_blue.png')}}" style="height: 24px;" alt=""> : On Job No Container <br>
                <img src="{{asset('img/truck_red.png')}}" style="height: 24px;" alt=""> : On Job With Container <br>
                <img src="{{asset('img/blue_mark.png')}}" style="height: 24px;" alt=""> : Dermaga <br>
                <img src="{{asset('img/red_marker.png')}}" style="height: 20px;" alt=""> : Depo <br>
              </div> -->

            </div>

            <div id="split2" class="split split-vertical">
              <div class="panel panel-default">
                <div class="panel-body">
                  <div id="timeline" style="width: 100%;">

                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
    <!-- Mainly scripts -->
    <script src="{{asset('js/jquery-3.1.1.min.js')}}"></script>
    <script src="{{asset('bower_components/angular/angular.min.js')}}"></script>
    <!-- <script src="{{asset('bower_components/oclazyload/dist/ocLazyLoad.min.js')}}"></script> -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="{{asset('js/plugins/metisMenu/jquery.metisMenu.js')}}"></script>
    <script src="{{asset('js/plugins/slimscroll/jquery.slimscroll.min.js')}}"></script>

    <!-- Custom and plugin javascript -->
    <script src="{{asset('js/plugins/chosen/chosen.jquery.js')}}"></script>
    <script src="{{asset('bower_components/angular-chosen/dist/angular-chosen.min.js')}}"></script>
    <script src="{{asset('js/inspinia.js')}}"></script>
    <script src="{{asset('js/plugins/pace/pace.min.js')}}"></script>
    <script src="{{asset('js/plugins/iCheck/icheck.min.js')}}"></script>
    <script src="{{asset('js/plugins/chosen/chosen.jquery.js')}}"></script>
    <!-- <script src="{{asset('gantt/jquery.stacked-gantt.min.js')}}"></script> -->
    <script src="{{asset('js/fullcalendar-3.9.0/lib/moment.min.js')}}"></script>
    <script src="{{asset('js/fullcalendar-3.9.0/fullcalendar.min.js')}}"></script>
<!-- AIzaSyCYHxT3gwgKMB59YAsXF0MG2oiVZwuNXu4 -->
    <script type="text/javascript" src="http://maps.google.com/maps/api/js?key=AIzaSyCo_7YS4SmXO6RD7TKxQCWpRazFVM3SF8A&libraries=geometry,drawing,places"></script>
    <!-- <script type="text/javascript" src="http://maps.google.com/maps/api/js?key=&libraries=geometry,drawing,places"></script> -->
    <script src="{{asset('js/split.min.js')}}"></script>
    <script src="{{asset('js/gmaps.js')}}"></script>
    <script type="text/javascript">
      var apiToken="{{auth()->user()->api_token}}"
      Split(['#split1','#split2'], {
        direction: 'vertical',
        sizes: [100,0],
        minSize: 0,
        cursor: 'row-resize'
      });
    </script>
    <script src="{{asset('controller/mapCtrl.js')}}"></script>

</body>

</html>
