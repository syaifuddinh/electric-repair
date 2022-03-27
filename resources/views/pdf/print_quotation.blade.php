<html>
<head>
<title>Cetak Penawaran</title>
</head>
<body style="font-family: arial;font-size: 12px">
	<div style='width:100%;padding-bottom:5mm;margin-bottom:5mm;border-bottom:1px double black'>
		<div style="display:inline-block;width:30%">
			<div style="height:25mm; position:relative;">
        @if($remarks->logo)
				<img src="{{ asset('files/'.$remarks->logo.'') }}" style="height:28mm;width:auto;position:absolute;" alt="">
        @endif
			</div>
		</div>
		<div style="display:inline-block;width:65%;text-align:center">
			<span style='font-weight:bold;'>{{ $remarks->person }}</span><br>
			<span style='font-weight:bold;'>Cabang {{ $item->company->name }}</span><br>
			<span>
				{{ $remarks->address }}
			</span><br>
			<span>
				Telp. {{ $remarks->phone }} (hunting) Fax. {{ $remarks->fax }}
			</span><br>
			<span>
				SMS Center : {{ $remarks->sms_center }}
			</span><br>
			<span>
				Email : {{ $remarks->email }}
			</span>
		</div>
	</div>
	<div>
		<div style='float:right'>
			{!! QrCode::size(200)->generate( route('print_quotation_by_slug', ['slug' => $item->path]) ); !!}
		</div>
		<table class="all-left" style="width: 70%">
			<tr>
				<td colspan="3">Jakarta, {{date('d F Y', strtotime(@$item->date_inquery))}}</td>
			</tr>
			<tr>
				<td style="width:40px;">No</td>
				<td style="width:2px;">:</td>
				<td>{{@$item->code}}</td>
			</tr>
			<tr>
				<td>Perihal</td>
				<td>:</td>
				<td>Penawaran Harga</td>
			</tr>
			<tr>
				<td>Attn</td>
				<td>:</td>
				<td>{{@$item->name}}</td>
			</tr>
		</table>
		<br>
		<table>
			<tr><td>Kepada Yth</td></tr>
			<tr><td>{{@$item->customer->name}}</td></tr>
			<tr><td>Ditempat</td></tr>
		</table>
		<br>
		Dengan Hormat, berikut daftar harga yang kami tawarkan<br>
		<br>
		<br>
		<table style="width: 100%; border-collapse: collapse;" border="1px">
			<tr>
				<th style="width: 5%">No.</th>
				<th style="width: 55%">Deskripsi</th>
				<th style="width: 20%">Pengenaan</th>
				<th style="width: 20%">Harga</th>
			</tr>
			<!-- tipe 1 -->
			<?php $totalAll=0; ?>
			@foreach($detail as $key => $value)
			<tr>
				<td style="text-align: center;">{{@$key+1}}</td>
				<td>
					<?php
					echo @$value->price_name??null.'<br>';
					if ($value->service_type_id==1) {
						echo @$value->trayek->name??null.' ';
						echo @$value->commodity->name??null.' ';
						echo @$value->vehicle_type->name??null.' ';
					} else if ($value->service_type_id==2) {
						echo @$value->trayek->name??null.' ';
						echo @$value->container_type->name??null.' ';
					} else if (in_array($value->service_type_id,[3,4])) {
						echo @$value->trayek->name??null.' ';
						echo @$value->vehicle_type->name??null.' ';
					} else {
						echo '';
					}
					?>
				</td>
				<td style="text-align: left;">
					<?php
					if ($value->service_type_id==1) {
						echo @$value->imposition_name;
					} elseif ($value->service_type_id==2) {
						echo 'Kontainer';
					} elseif ($value->service_type_id==3) {
						echo 'Unit';
					} elseif ($value->service_type_id==4) {
						echo @$value->piece->name??null;
					} elseif (in_array($value->service_type_id,[6,7])) {
						echo @$value->piece->name??null;
					} elseif ($value->service_type_id == null) {
						echo 'Paket';
					} else {
						echo '';
					}
					 ?>
				</td>
				<td style="text-align: right; padding-right:3px;">
					<?php
					if ($value->service_type_id==1) {
						if ($value->imposition==1) {
							echo formatNumber($value->price_inquery_volume);
							$totalAll+=$value->price_inquery_volume;
						} elseif ($value->imposition==2) {
							echo formatNumber($value->price_inquery_tonase);
							$totalAll+=$value->price_inquery_tonase;
						} else {
							echo formatNumber($value->price_inquery_item);
							$totalAll+=$value->price_inquery_item;
						}
					} else {
						echo formatNumber($value->price_inquery_full);
						$totalAll+=$value->price_inquery_full;
					}
					 ?>
				</td>
			</tr>

			@endforeach
		</table>
		<br>

		<!-- <p>Keterangan Quotation</p> -->

		{!! @$item->description_inquery !!}
		<p>Demikian penawaran ini kami sampaikan, besar harapan yang kami sampaikan sesuai dengan kebutuhan perusahaan. Atas perhatian dan kerja samanya kami ucapkan terima kasih.</p>

		Hormat kami <br>
		SOLOG<br>
		<br>
		<br>
		<br>
		{{@$item->user_create->name}} <br>
	</div>
</body>
</html>
