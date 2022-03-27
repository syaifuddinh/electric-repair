<style>
	.text-danger {
		font-weight: bold;
		color:red;
	}
	.text-right {
		text-align: right;
	}
	td, th {
		padding:1.4mm;
	}
</style>
<table border='1' cellspacing='0' class="table table-striped table-bordered table-hover">
	<thead>
		<tr>
			<th>No.</th>
			<th>No. Invoice</th>
			<th>Tanggal Invoice</th>
			<th>Dibuat Oleh</th>
			<th>Jumlah Edit Invoice</th>
			<th>Jumlah Batal Posting</th>
		</tr>
	</thead>
	<tbody>
		@foreach($units AS $idx => $unit)
			<tr>
				<td>{{ $idx + 1}}</td>
				<td>{{ $unit->code }}</td>
				<td>{{ fullDate($unit->created_at) }}</td>
				<td>{{ $unit->creator }}</td>
				<td class='text-right'>{{ $unit->qty_edit }}</td>
				<td class='text-right'>{{ $unit->qty_batal_posting }}</td>
			</tr>
		@endforeach
	</tbody>
</table>
