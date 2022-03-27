<html>
<head>
<title>Cetak Sales Order</title>
<style>
    .bordered-table {
        border:1px solid black;
        border-collapse: collapse;
    }
    .bordered-table tr > td, .bordered-table tr > th {
        border:1px solid black;
        border-collapse: collapse;
        padding: 10px 15px 15px 15px;
        vertical-align: top;
    }
    .text-right {
        text-align: right;
    }
    .text-center {
        text-align: center;
    }
    .footer {
        text-align: center;
        bottom: 0px;
    }
    h1 {
        font-size: 36pt;
    }
</style>
</head>
<body style="font-family: arial;font-size: 12px">
	<div style='width:100%;padding-bottom:5mm;margin-bottom:5mm;border-bottom:1px double black'>
		<!-- <div style="display:inline-block;width:30%">
			<div style="height:35mm; position:relative;">
            @if($remarks->logo)
				<img src="{{ asset('files/'.$remarks->logo.'') }}" style="height:38mm;width:auto;position:absolute;" alt="">
            @endif
			</div>
		</div>
        <div style="display:inline-block;width:60%">
            <span>
                <h1 style="color: red;">PT. SATONA</h1>
            </span>
        </div> -->
        <div style="display: block;">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 20%;">
                    @if($remarks->logo)
                        <img src="{{ asset('files/'.$remarks->logo.'') }}" style="height:38mm;width:auto;" alt="">
                    @endif
                    </td>
                    <td class="text-center">
                        <span>
                            <h1 style="color: red;">{{ $remarks->person }}</h1>
                            <p>Partner In Chemical Field</p>
                        </span>
                    </td>
                </tr>
            </table>
        </div>
	</div>
	<div>
        <div>
            <table style="width: 100%;" class="bordered-table">
                <tr>
                    <td style="width: 50%">
                        <h3>SALES ORDER</h3>
                    </td>
                    <td class="text-right">
                        Date : {{ $so->created_at ? date_create($so->created_at)->format('d F Y') : '-' }} <br>
                        Invoice : {{ $invoice && !empty($invoice->code) ? $invoice->code : '-' }}
                    </td>
                </tr>
                <tr>
                    <td>
                        <h3>{{ $company->name }}</h3>
                        <div>
                            {{ $company->address }} <br>
                            {{ $company->city }}, {{ $company->province }} <br>
                            {{ $company->phone }} <br>
                            {{ $company->email }}
                        </div>
                    </td>
                    <td>
                        <h4>Pengiriman ke:</h4>
                        <div class="text-right">
                            {{$customer->name }} <br>
                            {{$customer->address }} <br>
                            {{$customer->city }}, {{$customer->province}} <br>
                            {{$customer->phone ?? '-' }} <br>
                            {{$customer->email }} <br>
                        </div>
                    </td>
                </tr>
            </table>
            <table style="width: 100%; margin-top: 10px" class="bordered-table">
                <tr>
                    <th>Nama Sales</th>
                    <th>Jabatan</th>
                    <th>Metode Pengiriman</th>
                    <th>Istilah Pengiriman</th>
                    <th>Tanggal Pengiriman</th>
                    <th>Istilah Pembayaran</th>
                    <th>Jatuh Tempo</th>
                </tr>
                <tr>
                    <td>{{ $so->sales_name }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>{{ $so->shipment_date ? date_create($so->shipment_date)->format('d F Y') : '-' }}</td>
                    <td></td>
                    <td>{{ $invoice && !empty($invoice->due_date) ? date_create($invoice->due_date)->format('d F Y') : '-' }}</td>
                </tr>
            </table>
            <table style="width: 100%; margin-top: 10px" class="bordered-table">
                <tr>
                    <th style="width: 10%;">Jumlah</th>
                    <th style="width: 30%;">Barang #</th>
                    <th style="width: 20%;">Deskripsi</th>
                    <th style="width: 15%;">Harga</th>
                    <th style="width: 10%;">Satuan</th>
                    <th style="width: 15%;">Total per Satuan</th>
                </tr>
                @foreach($so_detail as $detail)
                <tr>
                    <td>{{ $detail->qty }}</td>
                    <td>{{ $detail->item_name }}</td>
                    <td>{{ $detail->description }}</td>
                    <td class="text-right">{{ number_format($detail->price, 0, ",", ".") }}</td>
                    <td>{{ $detail->unit_name }}</td>
                    <td class="text-right">{{ number_format($detail->total_price, 0, ",", ".") }}</td>
                </tr>
                @endforeach
                <tr class="text-right">
                    <td colspan="4"><b>Total Potongan</b></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr class="text-right">
                    <td colspan="5"><b>Sub Total</b></td>
                    <td>{{ $so_detail->isNotEmpty() ? number_format($so_detail->sum('total_price'), 0, ",", ".") : '0' }}</td>
                </tr>
                <tr class="text-right">
                    <td colspan="5"><b>PPN</b></td>
                    <td>{{ $ppn }}</td>
                </tr>
                <tr class="text-right">
                    <td colspan="5"><b>Total</b></td>
                    <td>{{ $so_detail->isNotEmpty() ? number_format($so_detail->sum('total_price') + $ppn, 0, ",", ".") : '0' }}</td>
                </tr>
            </table>
            <table style="width: 100%; margin-top: 10px" class="bordered-table">
                <tr>
                    <td style="width: 25%;"></td>
                    <td style="width: 35%;"></td>
                    <td style="width: 40%;" class="text-center">
                        Menyetujui, 
                        <br>
                        <br>
                        <br>
                        <br>
                        <br>
                        ( {{ $so->co_approver_name }} )
                    </td>
                </tr>
            </table>
        </div>
	</div>
    <div class="footer">
        <p>
            Address : {{ $remarks->address }} <br>
            Phone : {{ $remarks->phone }} Email : {{ $remarks->email }} <br>
            Website :
        </p>
    </div>
</body>
</html>
