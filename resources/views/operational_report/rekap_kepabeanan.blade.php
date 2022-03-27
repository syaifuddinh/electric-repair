<table class="table table-striped table-bordered table-hovered">
	<thead>
		<tr>
			<td>No</td>
			<td>Customer</td>
			<td>Layanan</td>
			<td>Jml WO</td>
			<td>Satuan</td>
			<td>Qty</td>
			<td>Total Biaya</td>

		</tr>
	</thead>
	<tbody>
		@foreach($units AS $idx => $unit)
			<tr>
				<td>{{ $idx + 1}}</td>
				<td>{{ $unit->customer }}</td>
				<td>{{ $unit->service }}</td>
				<td>{{ $unit->qty_wo }}</td>
				<td>{{ $unit->satuan }}</td>
				<td>{{ $unit->qty }}</td>
				<td>{{ $unit->total_price }}</td>
			</tr>
		@endforeach
	</tbody>
</table>