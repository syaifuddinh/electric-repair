<html>
<head>
<title>WORK ORDER</title>
<style type="text/css">
	td {
		 vertical-align: text-top;
	}
	.font-bold {
		font-weight: bold;
	}
</style>
</head>

<body style="font-family: arial;font-size: 12px">
	<div style="width: 98%; height: 9%;">
	</div>
	<div style="margin-top:1%;">
		<center><h2>WORK ORDER</h2></center>

		<table style="width: 100%; ">
			<tr>
				<td style="width: 48%;vertical-align: text-top;">
					<table style="width: 100%">
						<tr>
							<td style="width: 20%">Cabang</td>
							<td style="width:5%;">:</td>
							<!-- jika is_full = 1 maka lihat receiver_id pada job order jika is_full = 0 maka isinya "-" -->
							<td class="font-bold">{{$item->customer->company->name}}</td>
						</tr>
						<tr>
							<td>Customer</td>
							<td>:</td>
							<!-- jika is_full = 1 maka lihat alamat dari receiver_id pada job order jika is_full = 0 maka isinya "-" -->
							<td>{{$item->customer->name}}</td>
						</tr>
						<tr>
							<td>No WO</td>
							<td>:</td>
							<!-- jika is_full = 1 maka lihat phone dari receiver_id pada job order jika is_full = 0 maka isinya "-" -->
							<td class="font-bold">{{$item->code}}</td>
						</tr>
						<tr>
							<td>Tanggal WO</td>
							<td>:</td>
							<!-- kosong -->
							<td>{{Carbon\Carbon::parse($item->created_at)->format('d M Y')}}</td>
						</tr>
						<tr>
							<td>No Kontrak</td>
							<td>:</td>
							<!-- kosong -->
							<td>{{$item->quotation->no_contract or '-'}}</td>
						</tr>
						<tr>
							<td>Tgl Kontrak</td>
							<td>:</td>
							<!-- kosong -->
							<td>{{$item->quotation_id ? Carbon\Carbon::parse($item->quotation->date_contract)->format('d M Y') : '-'}}</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>

		<br>

		<table style="width: 100%; border-collapse: collapse;" border="1">
			<tr>
				<th>No.</th>
				<th>Layanan</th>
				<th>Trayek</th>
				<th>Komoditas</th>
				<th>Tipe Kendaraan</th>
				<th>Tipe Kontainer</th>
				<th>Pengenaan</th>
				<th>Harga</th>
				<th>Jumlah JO</th>
				<th>Status</th>
				<th>Keterangan</th>
			</tr>

			<!-- Manifest detail join job oder detail -->
			<?php if ($item->quotation_id): ?>
				<?php foreach ($detail as $key => $value): ?>
					<tr>
						<td>{{$key+1}}</td>
						<td>{{$value->quotation_detail->service->name or '-'}}</td>
						<td>{{$value->quotation_detail->route->name or '-'}}</td>
						<td>{{$value->quotation_detail->commodity->name or '-'}}</td>
						<td>{{$value->quotation_detail->vehicle_type->name or '-'}}</td>
						<td>{{$value->quotation_detail->container_type->full_name or '-'}}</td>
						<td>
							<?php
								$qd=$value->quotation_detail;
								if (in_array($qd->service_type_id,[6,7])) {
									echo @$qd->piece->name;
								} elseif ($qd->service_type_id==2) {
									echo "Kontainer";
								} elseif ($qd->service_type_id==3) {
									echo "Unit";
								} else {
									$imp=[
										1=>'Volume',
										2=>'Tonase',
										3=>'Item',
									];
									echo $imp[$qd->imposition];
								}
							?>
						</td>
						<td>
							Rp. <?php echo formatNumber($value->quotation_detail->price_contract_tonase+$value->quotation_detail->price_contract_volume+$value->quotation_detail->price_contract_item+$value->quotation_detail->price_contract_full); ?>
						</td>
						<td>{{$value->total_jo or 0}}</td>
						<td>{{$value->is_done?'Selesai':'Proses'}}</td>
						<td>{{$value->description}}</td>
					</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<?php foreach ($detail as $key => $value): ?>
					<tr>
						<td>{{$key+1}}</td>
						<td>{{$value->price_list->service->name or '-'}}</td>
						<td>{{$value->price_list->route->name or '-'}}</td>
						<td>{{$value->price_list->commodity->name or '-'}}</td>
						<td>{{$value->price_list->vehicle_type->name or '-'}}</td>
						<td>{{$value->price_list->container_type->full_name or '-'}}</td>
						<td>
							<?php
								$qd=$value->price_list;
								if (in_array($qd->service_type_id,[6,7])) {
									echo @$qd->piece->name;
								} elseif ($qd->service_type_id==2) {
									echo "Kontainer";
								} elseif ($qd->service_type_id==3) {
									echo "Unit";
								} else {
									echo "Tonase/Kubikasi/Item";
								}
							?>
						</td>
						<td>
							Rp. <?php echo formatNumber($value->price_list->price_full); ?>
						</td>
						<td>{{$value->total_jo or 0}}</td>
						<td>{{$value->is_done?'Selesai':'Proses'}}</td>
						<td>{{$value->description}}</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</table>

		<br>

	</div>
</body>
</html>
