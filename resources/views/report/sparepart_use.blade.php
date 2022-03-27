<div class='row'>"
    <div class='col-md-12 text-center'>
        <h2 class='font-bold'>LAPORAN PENGGUNAAN SPAREPART</h2>
        @if (isset($filters['company_name']))
            <h5 class='font-bold'>{{ $filters['company_name'] }}</h5>
        @endif
        @if (isset($filters['vehicle_nopol']))
            <h5 class='font-bold'>Kendaraan {{ $filters['vehicle_nopol'] }}</h5>
        @endif
        @if (isset($filters['periode_pemakaian']))
            <h5 class='font-bold'>Periode Pemakaian {{ $filters['periode_pemakaian']['start'] }} s/d {{ $filters['periode_pemakaian']['end'] }}</h5>
        @endif
        <br/>
    </div>
</div>

<table class="table table-striped table-bordered table-hover">
	<thead>
		<tr>
			<th>No</th>
			<th>Cabang</th>
			<th>Kode Perawatan</th>
			<th>Tanggal Pengajuan</th>
			<th>Tanggal Realisasi</th>
			<th>Nopol Kendaraan</th>
			<th>Perawatan</th>
			<th>Item</th>
            <th>Qty</th>
            <th>Biaya</th>
            <th>Total Biaya</th>
		</tr>
	</thead>
	<tbody>
		@foreach($data AS $idx => $unit)
			<tr>
				<td>{{ $idx + 1}}</td>
				<td>{{ $unit->cabang }}</td>
				<td>{{ $unit->code }}</td>
				<td>{{ date('d-m-Y', strtotime($unit->date_pengajuan)) }}</td>
				<td>{{ date('d-m-Y', strtotime($unit->date_realisasi)) }}</td>
				<td>{{ $unit->nopol }}</td>
				<td>{{ $unit->perawatan }}</td>
				<td>{{ $unit->item_name }}</td>
                <td class="text-right">{{ $unit->qty_realisasi }}</td>
                <td class="text-right">{{ formatNumber($unit->cost_realisasi) }}</td>
                <td class="text-right">{{ formatNumber($unit->total_realisasi) }}</td>
			</tr>
		@endforeach
	</tbody>
</table>
