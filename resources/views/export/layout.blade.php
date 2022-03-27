<html lang="en" dir="ltr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title></title>
  </head>
  <body>
    <style media="screen">

                @page {
                  margin: 0.5cm 0.5cm;
                }
                /* body{
                    font-family: arial;font-size:12px;
                } */
                h1{
                    /* font:Arial, Helvetica, sans-serif bold 14px; */
                    margin-top: 0px;
                    margin-bottom:2px;
                    text-align: center;
                }
                h2{
                    font:Arial, Helvetica, sans-serif bold 13px; margin-top: 0px;
                    margin-bottom:2px;
                    text-align: center;
                }

                table { page-break-inside:auto }
                tr    { page-break-inside:avoid; page-break-after:auto }
                thead { display:table-header-group }
                tfoot { display:table-footer-group }

                table.utama
                    {
                        border-width: 1px;
                        border-collapse: collapse;
                        border-style: solid;
                    }

                table.table-borderless tbody tr td {
                  border: none;
                  font-size: 13px;
                }
                table.mepet tbody tr td {
                  padding: 1px;
                }

                .utama td, .utama th
                {
                    margin: 0;
                    padding:2px;
                    border :solid 1px;
                    vertical-align: top;
                }
                 table.dalam {
                    border: 0px none;
                    margin:0px;
                }
                .dalam td{
                    vertical-align: top;
                    padding:2px;
                    border: 0px; text-align: center;
                }
                .invoice .logo {
                  margin-bottom: 10px;text-align: right;
                }
                input[type="checkbox"]{
                  width: 30px; /*Desired width*/
                  height: 30px; /*Desired height*/
                }
                table.dalam2{
                    width: 100%;
                    margin-top: 0px;
                }
                 .dalam2 th, .dalam2 td  {
                    vertical-align: bottom; padding-bottom: 2px; border: 0px; text-align: left;
                }
            .style1 {
    	font-size: 16px;
    	font-weight: bold;
    }
    .style3 {font-size: medium}
    .bold {
      font-weight: bold;
    }

    .text-right {
      text-align: right;
    }
    .va--top tr td{
      vertical-align: top;
    }
    table tr th, td {
      font-size: 13px;
      padding: 6px;
      border: 0.7px solid black;
    }
    </style>
    <div class="container">
      @yield('content')
    </div>
  </body>
</html>
