<!DOCTYPE html>
<html>
<head>
<title>SURAT JALAN</title>
<style type="text/css">
	td {
		 vertical-align: text-top;
	}
	.font-bold {
		font-weight: bold;
	}
	body {
		font-size: 13px;
	}
	.tab tbody tr td {
		padding: 5px;
	}
</style>
</head>

<body>
	<div style="width: 98%; height: 9%;">
	</div>
	<div style="width: 30%; position: fixed; right: 10px; text-align: right;">
    	<img src="data:image/png;base64, {!! base64_encode(QrCode::format('png')->margin(0)->size(100)->generate(url('/shipment').'?uniqid='.$detail_one->job_order_detail->job_order->uniqid)) !!} ">

	</div>
	<div style="margin-top:1%;">
		<center><h2>DELIVERY ORDER</h2></center>

		<table style="width: 100%; ">
			<tr>
				<td style="width: 48%;vertical-align: text-top;">
					<table style="width: 100%">
						<tr>
							<td style="width: 20%">Deliver To</td>
							<td style="width:5%;">:</td>
							<!-- jika is_full = 1 maka lihat receiver_id pada job order jika is_full = 0 maka isinya "-" -->
							<td class="font-bold">{{$item->is_full==1?@$detail_one->job_order_detail->job_order->receiver->name:'-'}}</td>
						</tr>
						<tr>
							<td>Address</td>
							<td>:</td>
							<!-- jika is_full = 1 maka lihat alamat dari receiver_id pada job order jika is_full = 0 maka isinya "-" -->
							<td>{{$item->is_full==1?@$detail_one->job_order_detail->job_order->receiver->address:'-'}}</td>
						</tr>
						<tr>
							<td>Phone</td>
							<td>:</td>
							<!-- jika is_full = 1 maka lihat phone dari receiver_id pada job order jika is_full = 0 maka isinya "-" -->
							<td>{{$item->is_full==1?@$detail_one->job_order_detail->job_order->receiver->phone:'-'}}</td>
						</tr>
						<tr>
							<td>Receiver</td>
							<td>:</td>
							<!-- kosong -->
							<td></td>
						</tr>
					</table>
				</td>
				<td style="width: 4%">

				</td>
				<td style="width: 48%;vertical-align: text-top;">
					<table style="width: 75%">
						<tr>
							<td style="width: 20%;">B/L No.</td>
							<td style="width:5%;">:</td>
							<!-- jika is_full = 1 maka lihat alamat dari No BL pada job order jika is_full = 0 maka isinya "-" -->
							<td>{{$item->is_full==1?@$detail_one->job_order_detail->job_order->no_bl:'-'}}</td>
						</tr>
						<tr>
							<td>Vessel No.</td>
							<td>:</td>
							<!-- container_id join jadwal kapal nama kapal + voyage -->
							<td>{{@$item->container->voyage_schedule->vessel->name.' - '.@$item->container->voyage_schedule->voyage}}</td>
						</tr>
						<tr>
							<td>Container No.</td>
							<td>:</td>
							<!-- container_id join jadwal kapal nama kapal + voyage -->
							<td>{{$item->container_id?$item->container->container_no:$item->container_no}}</td>
						</tr>
						<tr>
							<td>Vehicle No.</td>
							<td>:</td>
							<td>{{$sj->nopol}}</td>
						</tr>
						<tr>
							<td>Driver</td>
							<td>:</td>
							<td>{{$sj->driver}}</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>

		<br>

		<table class="tab" style="width: 100%; border-collapse: collapse;" border="1">
			<tr>
				<th>No.</th>
				<th>Description of Cargo</th>
				<th>Weight Measurement</th>
				<th>Number of Package</th>
				<th>Remark</th>
			</tr>

			<!-- Manifest detail join job oder detail -->
			@foreach($detail as $key => $value)
			<tr>
				<td>{{$key+1}}</td>
				<td>{{$value->item_name}}</td>
				<td>
					<?php
					echo ($value->weight??0).' Kg<br>';
					echo ($value->volume??0).' m3';
					 ?>
				</td>
				<td>
					<?php
					echo number_format($value->transported,0,',','.').' '.$value->piece;
					 ?>
				</td>
				<td>-</td>
			</tr>
			@endforeach
		</table>

		<br>

		<table style="width: 100%;border-collapse: collapse;" border="1">
			<tr>
				<td style="text-align: center; width: 30%">
					Surabaya, <br>
					SOLOG
					<br>
					<br>
					<br>
					<br>
					<br>
					<center><u style="text-align: center; width: 100%">{{$item->user_create->name}}</u></center>
				</td>
				<td style="text-align: center; width: 30%">
					Driver <br>
					Transporter
					<br>
					<br>
					<br>
					<br>
					<br>
					<!--
						Jika is_container = true
							ambil driver pada tabel manifest
						Jika is_container = false
							ambil dari driver_id
					-->
					<u>{{@$sj->driver}}</u>
				</td>
				<td style="text-align: center;width: 30%">
					Cargo Received in good<br>
					condition by
					<br>
					<br>
					<br>
					<br>
					<br>
					<center><u style="text-align: center; width: 100%">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u></center>

				</td>
			</tr>
		</table>
	</div>
</body>
</html>
