<table class="table table-striped table-bordered table-hovered">
	<thead>
		<tr>
			<td>No</td>
			<td>Customer</td>
			<td>WO</td>
			<td>Tanggal WO</td>
			<td>Layanan</td>
			<td>Satuan</td>
			<td>Tipe Kendaraan</td>
			<td>Qty</td>
			<td>Total Biaya</td>
			<td>Keterangan</td>
		</tr>
	</thead>
	<tbody>
		@foreach($units AS $idx => $unit)
			<tr>
				<td>{{ $idx + 1}}</td>
				<td>{{ $unit->customer }}</td>
				<td>{{ $unit->code_wo }}</td>
				<td>{{ $unit->date_wo }}</td>
				<td>{{ $unit->service }}</td>
				<td>{{ $unit->satuan }}</td>
				<td>{{ $unit->vehicle_type }}</td>
				<td>{{ $unit->qty }}</td>
				<td>{{ $unit->biaya }}</td>
				<td>{{ $unit->description }}</td>
			</tr>
		@endforeach
	</tbody>
</table>