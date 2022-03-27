<html lang="en">
  <head>
    <title>@yield('title')</title>
    <style media="all" lang="scss">
      body {
        font-family: arial;
        font-size: 5px;
      }
      table {
        page-break-inside: auto;
      }
      table tr {
        page-break-inside: avoid; page-break-after: auto;
      }
      table thead tr th {
        padding: 2px;
      }
      header { position: relative; left: 0px; right: 0px; }
      footer { position: fixed; bottom: -60px; left: 0px; right: 0px; background-color: lightblue; height: 50px; }
      main { position: relative; top: 140px; left: 0px; right: 0px;}
      p { page-break-after: always; }
      p:last-child { page-break-after: never; }
      strong{font-size:24px;}
      .td{border-left-width:1px;border-right-width: 1px;border-top-width: 0px;border-bottom-width: 0px;border-collapse: collapse;border-style: solid;}
      /* .th{border-left-width:0px;border-right-width: 0px;border-top-width: 0px;border-bottom-width: 1px;border-style: solid;} */
      .th{border-width:0px 0px 2px 0px; border-style: solid; border-collapse: collapse; border-color: black;}
      .border-top{border-width:1px 0px 0px 0px; border-style: solid; border-collapse: collapse; border-color: black;}
      .bold {font-weight: bold;}

    </style>
  </head>
  <body>
    <header>
      <div style="margin: 0 auto; width: 100px;">
        <img src="{{public_path('img/tpr.png')}}" style="width:100px; max-height:150px;">
      </div>
    </header>
    <!-- <footer>footer on each page</footer> -->
    <main>
        <div class="container">
        @yield('content')
        </div>
    </main>
  </body>
</html>
