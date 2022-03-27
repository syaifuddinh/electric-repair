<table class="table table-striped table-bordered table-hovered">
	<thead>
		<tr>
			<td>No</td>
			<td>Customer</td>
			<td>No. WO</td>
			<td>Tgl. Wo</td>
			<td>Layanan</td>
			<td>Komoditas</td>
			<td>Satuan</td>
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
				<td>{{ $unit->komoditas }}</td>
				<td>{{ $unit->satuan }}</td>
				<td>{{ $unit->qty }}</td>
				<td>{{ $unit->total_price }}</td>
				<td>{{ $unit->description }}</td>
			</tr>
		@endforeach
	</tbody>
</table>