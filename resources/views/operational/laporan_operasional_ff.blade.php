<html>
	<head>
		<style>
			thead { display: table-header-group }
			tfoot { display: table-row-group }
			tr { page-break-inside: avoid }
		</style>
	</head>
	<body>
		<div style="text-align: center;font-weight: bold;font-size:7mm">
		Laporan Operasional FF
		</div>


		@if(isset($request->start_date) AND isset($request->end_date))

			<div style="text-align: center;font-size:4.5mm;margin-top:2mm">
				Periode : {{ $request->start_date }} - {{ $request->end_date }}
			</div>
		@endif

		@if(isset($request->company_id))

			<div style="text-align: center;font-size:4.5mm;margin-top:2mm">
				Cabang : {{ $request->company_name }}
			</div>
		@endif

		@if(isset($request->service_id))

			<div style="text-align: center;font-size:4.5mm;margin-top:2mm">
				Golongan Layanan : {{ $request->service_name }}
			</div>
		@endif

		@if(isset($request->customer_id))
			<div style="text-align: center;font-size:4.5mm;margin-bottom:4mm;margin-top:2mm">
				Customer : {{$request->customer_name}}
			</div>
		@endif

		<table border='1' cellspacing='0' cellpadding='5' style='margin:auto;margin-top:6mm'>
			<thead>
				<tr style='font-weight:bold'>
					<td>No</td>
					<td>No WO</td>
					<td>Tanggal</td>
					<td>Customer</td>
					<td>No AJU</td>
					<td>No BL</td>
					<td>Layanan</td>
					<td>Qty</td>
					<td>Satuan</td>
					<td>Biaya Operasional</td>
				</tr>
			</thead>
			<tbody>
				@foreach($detail as $key => $value)
					<tr>
						<td>{{$key+1}}</td>
						<td>{{$value->code}}</td>
						<td>{{ dateView($value->date) }}</td>
						<td>{{$value->customer_name}}</td>
						<td>{{$value->aju_number}}</td>
						<td>{{$value->no_bl}}</td>
						<td>{{$value->service_name}}</td>
						<td>{{formatNumber($value->qty)}}</td>
						<td>{{ $value->type_tarif==1 ? $value->imposition_name : $value->imposition_name_pl }}</td>
						<td>{{ isset($value->cost) ? formatNumber($value->cost):0 }}</td>
					</tr>
				@endforeach
			</tbody>
		</table>
	</body>
</html>