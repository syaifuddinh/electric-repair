<?php
setlocale(LC_TIME, 'id_ID.utf8');
?>
<html ng-app="myApp">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{csrf_token()}}">

    <title>SOLOG</title>
    <link href="{{asset('css/plugins/toastr/toastr.min.css')}}" rel="stylesheet">
    <link href="{{asset('css/plugins/chosen/bootstrap-chosen.css')}}" rel="stylesheet">
    <link href="{{asset('css/selectize.bootstrap3.css')}}" rel="stylesheet">
    <link href="{{asset('css/bootstrap-3.3.7.min.css')}}" rel="stylesheet">
    <link href="{{asset('css/plugins/jQueryUI/jquery-ui.css')}}" rel="stylesheet">
    <link href="{{asset('bower_components/angular-loading-bar/build/loading-bar.min.css')}}" rel="stylesheet">
    <link href="{{asset('bower_components/summernote/dist/summernote.css')}}" rel="stylesheet">
    <link href="{{asset('css/plugins/dataTables/datatables.min.css')}}" rel="stylesheet">
    <link href="{{asset('css/animate.css')}}" rel="stylesheet">
    <link href="{{asset('css/plugins/codemirror/codemirror.css')}}" rel="stylesheet">
    <link href="{{asset('css/plugins/codemirror/ambiance.css')}}" rel="stylesheet">
    <link href="{{asset('css/style.css')}}" rel="stylesheet">
    <link href="{{asset('font-awesome/css/font-awesome.min.css')}}" rel="stylesheet">
    <link href="{{asset('css/plugins/iCheck/custom.css')}}" rel="stylesheet">
    <link href="{{asset('css/plugins/awesome-bootstrap-checkbox/awesome-bootstrap-checkbox.css')}}" rel="stylesheet">
    <link href="{{asset('css/plugins/datapicker/datepicker3.css')}}" rel="stylesheet">
    <link href="{{asset('css/plugins/clockpicker/clockpicker.css')}}" rel="stylesheet">
    <link href="{{asset('css/plugins/c3/c3.min.css')}}" rel="stylesheet">
    <link href="{{asset('css/font-awesome-animation.min.css')}}" rel="stylesheet">
    <link href="{{asset('css/bootstrap-tagsinput.css')}}" rel="stylesheet">

    <link href="{{asset('css/dropzone.css')}}" rel="stylesheet">
    <link href="{{asset('css/angucomplete.css')}}" rel="stylesheet">
    <link href="{{asset('css/plugins/dropzone/basic.css')}}" rel="stylesheet">
    <link href="{{asset('bower_components/froala-wysiwyg-editor/css/froala_editor.pkgd.min.css')}}" rel="stylesheet">
    <link href="{{asset('bower_components/froala-wysiwyg-editor/css/froala_style.min.css')}}" rel="stylesheet">
    <link rel="shortcut icon" href="{{asset('img/pilar.png')}}">

    <link href="{{asset('js/plugins/leaflet/leaflet.css')}}" rel="stylesheet">
    <link href="{{asset('js/plugins/leaflet-routing-machine/dist/leaflet-routing-machine.css')}}" rel="stylesheet">
    @stack('custom_css')
    @yield('custom_css')
    <link rel="stylesheet" href="{{asset('css/roboto.css')}}">

</head>

<body class="skin-1">
  <style media="screen">
    [ng\:cloak], [ng-cloak], [data-ng-cloak], [x-ng-cloak], .ng-cloak, .x-ng-cloak {
      display: none !important;
    }

    .mCSB_container {
      overflow:visible !important;
    }

    .hidden {
      display: none;
    }

    table.table {
      width: 100% !important;
    }

    .chosen-container {
        width:100%!important;
    }

    .table thead tr th {
      font-size: 13px;
    }
    .table thead.thcenter tr th {
      text-align: center;
    }
    .table tfoot tr th {
      font-size: 13px;
    }
    .table tbody tr td {
      font-size: 12px;
      padding-bottom: 5px;
      padding-top: 5px;
    }
    table.table-borderless tbody tr td {
      border: none;
      padding: 4px;
      font-size: 13px;
    }

    table.table-borderless.outline-border tbody tr:first-child td {
      /* border-left: 1px solid; */
      border-top: 1px solid;
    }
    table.table-borderless.outline-border tbody tr td:first-child {
      border-left: 1px solid;
    }
    table.table-borderless.outline-border tbody tr td:last-child {
      border-right: 1px solid;
    }
    table.table-borderless.outline-border tbody tr:last-child td {
      border-bottom: 1px solid;
    }

    .clockpicker-popover {
      z-index: 999999 !important;
    }

    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
        /* display: none; <- Crashes Chrome on hover */
        -webkit-appearance: none;
        margin: 0; /* <-- Apparently some margin are still there even though it's hidden */
    }

    div.dataTables_wrapper {
        width: 100%;
        margin: 0 auto;
    }
    [ui-view].ng-enter {
      -webkit-animation: fadeInRight 0.5s;
      animation: fadeInRight 0.5s;

      opacity: 0;
    }

    [ui-view].ng-enter-active {
      opacity: 1;
    }

  </style>
    <noscript>
      <div style="position: fixed; top: 0px; left: 0px; z-index: 3000;height: 100%; width: 100%; background-color: #FFFFFF">
        <p style="width: 25%; margin: 0 auto;margin-top: 50px; text-align: center;"><span class="fa fa-warning"></span> <br> Harap Nyalakan Javascript pada pengaturan web browser anda!</p>
      </div>
    </noscript>
    <div id="wrapper">

    <nav class="navbar-default navbar-static-side" role="navigation">
        <div class="sidebar-collapse">
          @include('layouts.sidebar')
        </div>
    </nav>

        <div id="page-wrapper" class="gray-bg">
        <div class="row border-bottom">
          @include('layouts.topbar')
        </div>
            <div class="row wrapper border-bottom white-bg page-heading animated fadeInDown">
              <div class="col-sm-10">
                <h2 class="font-weight-extra-bold" ng-cloak>
                  <% pageTitle %>
                </h2>
                <ui-breadcrumb></ui-breadcrumb>
              </div>
            </div>
            <div ng-controller="globalMessageController" ng-cloak>
              <div ng-if="hasError">
                <ul>
                  <li ng-repeat="value in message.error"><% value.message %></li>
                </ul>
              </div>
            </div>

            <div class="wrapper wrapper-content">
                <div class="animated fadeInDown">
                  <div class="row">
                    <div ui-view="" class="mains" ng-cloak >

                    </div>
                  </div>
                </div>
            </div>
            {{--
            <div class="footer">
              @include('layouts.footer')
            </div>
            --}}
        </div>
      </div>
    <div class="modal fade" id="modal_error" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true">
      <div class="modal-dialog" style="width: 70%;">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title" id="">Error</h4>
          </div>
          <div class="modal-body">
            <div id="responseError" style="max-height: 500px; overflow-y: auto;">

            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-rounded btn-default" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
    <!-- Mainly scripts -->
    <script src="{{asset('js/jquery-3.1.1.min.js')}}"></script>
    <!-- <script src="{{asset('js/selectize.min.js')}}"></script> -->

    <script src="{{asset('js/jSignature.js')}}"></script>
    <script src="{{asset('js/jSignature.CompressorBase30.js')}}"></script>
    <script src="{{asset('js/jSignature.CompressorSVG.js')}}"></script>
    <script src="{{asset('js/plugins/jquery-ui/jquery-ui.min.js')}}"></script>
    <!-- AngularJS Scripts  -->
    <script src="{{asset('bower_components/angular/angular.min.js')}}"></script>
    <script src="{{asset('bower_components/angular-animate/angular-animate.min.js')}}"></script>
    <script src="{{asset('bower_components/angular-sanitize/angular-sanitize.min.js')}}"></script>
    <script src="{{asset('bower_components/angular-ui-router/release/angular-ui-router.min.js')}}"></script>
    <script src="{{asset('bower_components/angular-resource/angular-resource.min.js')}}"></script>
    <script src="{{asset('bower_components/ngInfiniteScroll/build/ng-infinite-scroll.min.js')}}"></script>
    <script src="{{asset('bower_components/angular-chosen/dist/angular-chosen.min.js')}}"></script>
    <script src="{{asset('bower_components/ng-timeago/ngtimeago.js')}}"></script>
    <script src="{{asset('bower_components/algolia-autocomplete.js/dist/autocomplete.angular.min.js')}}"></script>
    <script src="{{asset('bower_components/angular-loading-bar/build/loading-bar.js')}}"></script>
    <script src="{{asset('bower_components/froala-wysiwyg-editor/js/froala_editor.pkgd.min.js')}}"></script>
    <script src="{{asset('bower_components/summernote/dist/summernote.js')}}"></script>
    <script src="{{asset('bower_components/angular-summernote/dist/angular-summernote.js')}}"></script>
    <script src="{{asset('js/bootstrap-tagsinput.min.js')}}"></script>
    <script src="{{asset('js/bootstrap-tagsinput-angular.min.js')}}"></script>
    <!-- <script src="{{asset('bower_components/angular-ui-switch/angular-ui-switch.min.js')}}"></script> -->
    <!-- <script src="{{asset('bower_components/algolia-autocomplete.js/dist/algoliasearch.angular.min.js')}}"></script> -->
    <script src="{{asset('js/ui-router-breadcrumbs.min.js')}}"></script>
    <script src="{{asset('js/tinymce.min.js')}}"></script>
    <!-- <script src="http://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script> -->
    <script src="{{asset('js/bootstrap.min.js')}}"></script>
    <!-- <script src="{{asset('bower_components/bootstrap/js/bootstrap.min.js')}}"></script> -->
    <!-- <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script> -->
    <script src="{{asset('js/plugins/metisMenu/jquery.metisMenu.js')}}"></script>

    <!-- Custom and plugin javascript -->
    <script src="{{asset('js/inspinia.js')}}"></script>
    <!-- <script src="{{asset('js/plugins/pace/pace.min.js')}}"></script> -->
    <script src="{{asset('js/plugins/slimscroll/jquery.slimscroll.min.js')}}"></script>
    <script src="{{asset('js/plugins/chosen/chosen.jquery.js')}}"></script>
    <script src="{{asset('js/plugins/dataTables/datatables.min.js')}}"></script>
    <script src="{{asset('js/plugins/leaflet/leaflet.js')}}"></script>
    <script src="{{asset('js/plugins/leaflet-routing-machine/dist/leaflet-routing-machine.min.js')}}"></script>

    <script src="{{asset('js/plugins/iCheck/icheck.min.js')}}"></script>
    <script src="{{asset('js/plugins/datapicker/bootstrap-datepicker.js')}}"></script>
    <script src="{{asset('js/plugins/clockpicker/clockpicker.js')}}"></script>
    <script src="{{asset('js/plugins/c3/c3.min.js')}}"></script>
    <script src="{{asset('js/plugins/d3/d3.min.js')}}"></script>
    <script src="{{asset('bower_components/chart.js/dist/Chart.min.js')}}"></script>
    <script src="{{asset('js/jquery.validate.min.js')}}"></script>
    <script src="{{asset('js/sweetalert.min.js')}}"></script>
    <script src="{{asset('js/jquery.autocomplete.js')}}"></script>
    <script src="{{asset('js/localization/messages_id.min.js')}}"></script>
    <script src="{{asset('js/angucomplete.js')}}"></script>
    <script src="{{asset('js/jquery.inputmask.min.js')}}"></script>
    <script src="{{asset('js/plugins/toastr/toastr.min.js')}}"></script>
    <script src="{{asset('js/dropzone.min.js')}}"></script>
    <script type="text/javascript">
      var baseUrl = "{{url('/')}}";
      var csrfToken = "{{csrf_token()}}";
      var compId = {!! auth()->user()->company_id !!};
      var authUser = {!! App\User::with('company')->where('id', auth()->id())->first(); !!};
      var dateNow = "{{date('d-m-Y')}}";
      var timeNow = "{{date('H:i')}}";
      $.fn.dataTable.ext.errMode = 'none';
      $.fn.dataTable.tables( {visible: true, api: true} ).columns.adjust();
      $.extend( true, $.fn.dataTable.defaults, {
        scrollX: true,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
      });
      var oldExportAction = function (self, e, dt, button, config) {
          if (button[0].className.indexOf('buttons-excel') >= 0) {
              if ($.fn.dataTable.ext.buttons.excelHtml5.available(dt, config)) {
                  $.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt, button, config);
              }
              else {
                  $.fn.dataTable.ext.buttons.excelFlash.action.call(self, e, dt, button, config);
              }
          } else if (button[0].className.indexOf('buttons-print') >= 0) {
              $.fn.dataTable.ext.buttons.print.action(e, dt, button, config);
          }
      };
      var newExportAction = function (e, dt, button, config) {
          var self = this;
          var oldStart = dt.settings()[0]._iDisplayStart;

          dt.one('preXhr', function (e, s, data) {
              // Just this once, load all data from the server...
              data.start = 0;
              data.length = 2147483647;

              dt.one('preDraw', function (e, settings) {
                  // Call the original action function
                  oldExportAction(self, e, dt, button, config);

                  dt.one('preXhr', function (e, s, data) {
                      // DataTables thinks the first item displayed is index 0, but we're not drawing that.
                      // Set the property to what it was before exporting.
                      settings._iDisplayStart = oldStart;
                      data.start = oldStart;
                  });

                  // Reload the grid with the original page. Otherwise, API functions like table.cell(this) don't work properly.
                  setTimeout(dt.ajax.reload, 0);

                  // Prevent rendering of the full data to the DOM
                  return false;
              });
          });

          // Requery the server with the new one-time export settings
          dt.ajax.reload();
      };
      // $.fn.dataTable.ajax.headers={'Authorization':'Bearer xxx'}
      $(document).ready(function () {

          $('.datepick').datepicker({
            todayBtn: "linked",
            keyboardNavigation: false,
            forceParse: false,
            calendarWeeks: true,
            autoclose: true,
            format: "dd-mm-yyyy"
          });

          $('.clockpick').clockpicker();

          // $.validator.messages.required = 'Kolom ini harus diisi!';
          $('.forms').validate({
            ignore: ":hidden:not(select)",
            rules: {
              password_again: {
                equalTo: "#password"
              },
            },
          });

          // $('.chosen').chosen({
          //   width: "100%",
          //   height: "100%",
          //   search_contains: true,
          //   disable_search_threshold: 5,
          //   allow_single_deselect: true,
          //   placeholder_text_single: "Tidak ada data ...",
          //   no_results_text: "Maaf, Keyword tidak ditemukan!",
          // });
      });

      // setInterval(function() {
      //   getNotifDriver();
      // },60000);

      // $(document).ready(function(event){
      //   getNotifDriver();
      // });

      toastr.options = {
        "closeButton": true,
        "debug": false,
        "progressBar": true,
        "preventDuplicates": false,
        "positionClass": "toast-top-right",
        "showDuration": "100",
        "hideDuration": "100",
        "timeOut": "7000",
        "extendedTimeOut": "2000",
        "showEasing": "swing",
        "hideEasing": "swing",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
      }
      var userProfile=authUser;
      // console.log(userProfile);
    </script>
    <!-- Core -->
    @include('layouts.angular-directive')
    <!-- End of dependencies -->
    @include('layouts.js')
    @stack('script')
    @yield('script')
    <!-- <script type="text/javascript">
      var pusher = new Pusher("f567fbefbc4745ac6500",{
        cluster : "ap1",
        encrypted : true
      });

      var pusher_channel = pusher.subscribe('notif');

      pusher_channel.bind('App\\Events\\Notif', function(data) {
        console.log(data);
        if (data.type=="INFO") {
          toastr.info(data.message,data.title,{onclick: function(evt) {
            window.location = data.url;
          }});
        } else {
          toastr.error(data.message,data.title,{onclick: function(evt) {
            window.location = data.url;
          }});
        }
        // this is called when the event notification is received...
      });

    </script> -->

</body>

</html>
