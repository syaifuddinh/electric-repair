<style>
	.text-danger {
		font-weight: bold;
		color:red;
	}
	td, th {
		padding:1.4mm;
	}
</style>
<table border='1' cellspacing='0' class="table table-striped table-bordered table-hover">
	<thead>
		<tr>
			<th>No.</th>
			<th>No. JO</th>
			<th>Tanggal Pembuatan JO</th>
			<th>No. Invoice</th>
			<th>Pembuat JO</th>
			<th>Tanggal Penyelesaian</th>
			<th>Tanggal Target Pembuatan Invoice</th>
			<th>Tanggal Pembuatan Invoice</th>
		</tr>
	</thead>
	<tbody>
		@foreach($units AS $idx => $unit)
			<?php 
				$is_danger = '';
				if($unit->planning_date != null && $unit->date_invoice != null) {
					$planning_date = Carbon\Carbon::parse($unit->planning_date);
					$date_invoice = Carbon\Carbon::parse($unit->date_invoice);
					if($date_invoice->gt($planning_date)) {
						$is_danger = 'text-danger';						
					}
				}
			 ?>
			<tr>
				<td class='{{ $is_danger }}'>{{ $idx + 1}}</td>
				<td class='{{ $is_danger }}'>{{ $unit->job_order_code }}</td>
				<td class='{{ $is_danger }}'>{{ fullDate($unit->created_at) }}</td>
				<td class='{{ $is_danger }}'>{{ $unit->invoice_code }}</td>
				<td class='{{ $is_danger }}'>{{ $unit->creator }}</td>
				<td class='{{ $is_danger }}'>{{ fullDate($unit->finished_date) }}</td>
				<td class='{{ $is_danger }}'>{{ fullDate($unit->planning_date) }}</td>
				<td class='{{ $is_danger }}'>{{ fullDate($unit->date_invoice) }}</td>
			</tr>
		@endforeach
	</tbody>
</table>
