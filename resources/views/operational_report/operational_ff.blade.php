<table class="table table-striped table-bordered table-hover">
	<thead>
		<tr>
			<th>No</th>
			<th>No WO</th>
			<th>Tanggal</th>
			<th>Customer</th>
			<th>No AJU</th>
			<th>No BL</th>
			<th>Layanan</th>
			<th>Qty</th>
			<th>Satuan</th>
			<th class="text-right">Biaya Operasional</th>
		</tr>
	</thead>
	<tbody>
		@foreach($units AS $idx => $unit)
			<tr>
				<td>{{ $idx + 1}}</td>
				<td>{{ $unit->code }}</td>
				<td>{{ $unit->date }}</td>
				<td>{{ $unit->customer_name }}</td>
				<td>{{ $unit->aju_number }}</td>
				<td>{{ $unit->no_bl }}</td>
				<td>{{ $unit->service_name }}</td>
				<td>{{ $unit->qty }}</td>
				<td>{{ $unit->imposition_name }}</td>
				<td class="text-right"><a ng-click="detailCost('{{$unit->joc_list}}')">{{ number_format($unit->cost) }}</a></td>
			</tr>
		@endforeach
	</tbody>
</table>
