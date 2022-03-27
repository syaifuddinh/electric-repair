<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Job Order</title>

    <link href="{{asset('css/bootstrap-3.3.7.min.css')}}" rel="stylesheet">
    <!-- <link href="{{asset('css/style.css')}}" rel="stylesheet"> -->
    <link href="{{asset('font-awesome/css/font-awesome.css')}}" rel="stylesheet">
    <style media="screen">
      @font-face {
        font-family: "Consolas2";
    	  src: url("//db.onlinewebfonts.com/t/1db29588408eadbd4406aae9238555eb.eot");
    	  src: url("//db.onlinewebfonts.com/t/1db29588408eadbd4406aae9238555eb.eot?#iefix") format("embedded-opentype"),
    	  url("//db.onlinewebfonts.com/t/1db29588408eadbd4406aae9238555eb.woff2") format("woff2"),
    	  url("//db.onlinewebfonts.com/t/1db29588408eadbd4406aae9238555eb.woff") format("woff"),
    	  url("//db.onlinewebfonts.com/t/1db29588408eadbd4406aae9238555eb.ttf") format("truetype"),
    	  url("//db.onlinewebfonts.com/t/1db29588408eadbd4406aae9238555eb.svg#Consolas") format("svg");
      }
      .none {
        display: none;
      }
      body {
        font-family: Consolas;font-size:13px;
      }
      .table-borderless {
        width:100%;
      }
      table.table-borderless tbody tr td {
        border: none;
        padding: 4px;
        font-size: 13px;
      }
      table.table-border {
        width: 100%;
      }
      table.table-border thead tr th {
        border: 1px solid;
        padding: 4px;
        font-size: 13px;
        font-weight: bold;
      }
      table.table-border tbody tr td {
        border: 1px solid;
        padding: 4px;
        font-size: 13px;
      }

      .font-bold {
        font-weight: bold;
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

    </style>
  </head>
  <body class="top-navigation">
    <div id="page-wrapper" class="gray-bg">
      <div class="ibox" style="padding-top:15px; padding-left:15px; padding-right:15px;">
        <div class="ibox-content">
          <div class="row">
            <div class="col-md-12 col-xs-12">
              <img src="{{asset('img/tpr.png')}}" style="max-width:20%;" alt="">
            </div>
            <div class="col-md-8 col-sm-10 col-xs-8" style="padding-top:10px;">
              <h5 style="margin-left:0.3em">Rincian Job Order</h5>
              <table class="table-borderless">
                <tbody>
                  <tr>
                    <td style="width:25%;">No. Job Order</td>
                    <td style="width:2%;">:</td>
                    <td><span class="font-bold">{{$item->code}}</span> </td>
                  </tr>
                  <tr>
                    <td>No. Work Order</td>
                    <td>:</td>
                    <td><span class="font-bold">{{$item->wo_code}}</span> </td>
                  </tr>
                  <tr>
                    <td>No. PO Customer</td>
                    <td>:</td>
                    <td><span class="font-bold">{{$item->no_po_customer}}</span> </td>
                  </tr>
                  <tr>
                    <td>Customer</td>
                    <td>:</td>
                    <td><span class="font-bold">{{$item->customer_name}}</span> </td>
                  </tr>
                  <?php if (!in_array($item->service_type_id,[6,7])): ?>
                    <tr>
                      <td>Pengirim</td>
                      <td>:</td>
                      <td><span class="font-bold">{{$item->sender_name}} <br> {{$item->sender_address}}</span> </td>
                    </tr>
                    <tr>
                      <td>Penerima</td>
                      <td>:</td>
                      <td><span class="font-bold">{{$item->receiver_name}} <br> {{$item->receiver_address}}</span> </td>
                    </tr>
                  <?php endif; ?>
                  <tr>
                    <td>Tanggal Job Order</td>
                    <td>:</td>
                    <td><span class="font-bold">{{Carbon\Carbon::parse($item->shipment_date)->format('d F Y')}}</span> </td>
                  </tr>
                  <tr>
                    <td>Keterangan</td>
                    <td>:</td>
                    <td><span class="font-bold">{{$item->description}}</span> </td>
                  </tr>
                  <tr>
                    <td>Nama Layanan</td>
                    <td>:</td>
                    <td><span class="font-bold">{{$item->service}} <br> {{$item->service_type}}</span> </td>
                  </tr>
                  <tr>
                    <td>Trayek</td>
                    <td>:</td>
                    <td><span class="font-bold">{{$item->trayek}}</span> </td>
                  </tr>
                  <?php if ($item->service_type_id==6): ?>
                    <tr>
                      <td>Nama Dokumen</td>
                      <td>:</td>
                      <td><span class="font-bold">{{$item->document_name}}</span> </td>
                    </tr>
                    <tr>
                      <td>No. Reff</td>
                      <td>:</td>
                      <td><span class="font-bold">{{$item->reff_no}}</span> </td>
                    </tr>
                    <tr>
                      <td>No. Dokumen</td>
                      <td>:</td>
                      <td><span class="font-bold">{{$item->docs_no}}</span> </td>
                    </tr>
                    <tr>
                      <td>No. Reff Dokumen</td>
                      <td>:</td>
                      <td><span class="font-bold">{{$item->docs_reff_no}}</span> </td>
                    </tr>
                    <tr>
                      <td>Nama Kapal</td>
                      <td>:</td>
                      <td><span class="font-bold">{{$item->vessel_name}}</span> </td>
                    </tr>
                    <tr>
                      <td>Voyage</td>
                      <td>:</td>
                      <td><span class="font-bold">{{$item->voyage_no}}</span> </td>
                    </tr>
                  <?php endif; ?>

                  @foreach ($item->additional as $i => $v)
                    <tr>
                      <td>{{ $i }}</td>
                      <td>:</td>
                      <td><span class="font-bold">{{ $v }}</span> </td>
                    </tr>

                  @endforeach

                  <tr>
                    <td>Status Job Order</td>
                    <td>:</td>
                    <td><span class="font-bold">{{$item->status_name}}</span> </td>
                  </tr>

                </tbody>
              </table>
            </div>
            <div class="col-md-4 col-sm-2 col-xs-4" style="text-align:right;">
              <img src="data:image/png;base64, {!! base64_encode(QrCode::format('png')->margin(0)->size(150)->generate(url('/shipment').'?uniqid='.$item->uniqid)) !!} ">
            </div>
            <div class="col-md-12 col-xs-12 {{!in_array($item->service_type_id,[1,2,3,4]) ? 'none':''}}">
              <h5><i class="fa fa-cubes"></i> Item Barang</h5>
              <div class="table-responsive">
                <table class="table-border">
                  <thead>
                    <tr>
                      <th>Nama Barang</th>
                      <th>Satuan</th>
                      <th>Reff</th>
                      <th>No. Manifest</th>
                      <th>Qty</th>
                      <th>Tonase (Kg)</th>
                      <th>Kubikasi (m3)</th>
                      <th>Terangkut</th>
                      <th>Keterangan</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($detail as $value): ?>
                      <tr>
                        <td>{{$value->item_name}}</td>
                        <td>{{$value->piece}}</td>
                        <td>{{$value->no_reff}}</td>
                        <td>{{$value->no_manifest}}</td>
                        <td style="width:8%;">{{$value->qty}}</td>
                        <td style="width:8%;">{{$value->weight}}</td>
                        <td style="width:8%;">{{$value->volume}}</td>
                        <td style="width:8%;">{{$value->transported}}</td>
                        <td>{{$value->description}}</td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <div>
                <h5><i class="fa fa-ship"></i> Manifest / Packing List</h5>
                <div class="table-responsive">
                  <table class="table-border">
                    <thead>
                      <tr>
                        <th>No. Manifest</th>
                        <th>{{$item->service_type_id==2?'Tipe Kendaraan':'Tipe Kontainer'}}</th>
                        <th>{{$item->service_type_id==2?'Nopol':'Kapal'}}</th>
                        <th>{{$item->service_type_id==2?'Driver':'Kontainer'}}</th>
                        <th>Status</th>
                        <th>Tanggal Bongkar</th>
                        <th>Tanggal Muat</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($manifest as $value): ?>
                        <tr>
                          <td>{{$value->code}}</td>
                          <td>{{$item->service_type_id==2?$value->vname:$value->cname}}</td>
                          <td>{{$item->service_type_id==2?$value->nopol:$value->voyage}}</td>
                          <td>{{$item->service_type_id==2?$value->driver:$value->container}}</td>
                          <td>{{$value->status_name}}</td>
                          <td>{{$value->stripping}}</td>
                          <td>{{$value->stuffing}}</td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>

            </div>
          </div>

        </div>
      </div>
    </div>
    <script src="{{asset('js/jquery-3.1.1.min.js')}}"></script>
    <script src="{{asset('js/bootstrap.min.js')}}"></script>
    <script src="{{asset('bower_components/angular/angular.min.js')}}"></script>
    <!-- <script src="{{asset('js/inspinia.js')}}"></script> -->
  </body>
</html>
