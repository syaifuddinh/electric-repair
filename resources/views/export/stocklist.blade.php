<table>
  <thead>
    <tr>
        <th rowspan='2'>No</th>
        <th rowspan='2'>No TTB</th>
        <th rowspan='2'>Shipper</th>
        <th rowspan='2'>Consignee</th>
        <th rowspan='2'>Tujuan</th>
        <th rowspan='2'>Komoditas</th>
        <th rowspan='2'>Tanggal masuk</th>
        <th colspan='4'>Dimensi & berat</th>
        <th colspan='2'>Stok barang</th>
        <th rowspan='2'>Kub/Ton</th>
        <th rowspan='2'>Total Kub/Ton</th>
        <th rowspan='2'>Keterangan</th>
    </tr>
    <tr>
      <th></th>
      <th></th>
      <th></th>
      <th></th>
      <th></th>
      <th></th>
      <th></th>
      <th>P(cm)</th>
      <th>L(cm)</th>
      <th>T(cm)</th>
      <th>B(kg)</th>
      <th>Sisa</th>
      <th>Satuan</th>
    </tr>
  </thead>
  <tbody>
      @foreach($units as $index => $value)
          <tr>
              <td>{{ $index + 1 }}</td>
              <td>{{ $value->no_surat_jalan }}</td>
              <td>{{ $value->sender }}</td>
              <td>{{ $value->receiver }}</td>
              <td>{{ $value->city_to }}</td>
              <td>{{ $value->name }}</td>
              <td>{{ $value->receive_date }}</td>
              <td>{{ $value->long }}</td>
              <td>{{ $value->wide }}</td>
              <td>{{ $value->height }}</td>
              <td>{{ $value->tonase }}</td>
              <td>{{ $value->qty }}</td>
              <td>{{ $value->piece_name }}</td>
              <td>{{ $value->long * $value->wide * $value->height  }}</td>
              <td>{{ $value->long * $value->wide * $value->height * $value->qty  }}</td>
              <td>{{ $value->description }}</td>
          </tr>

      @endforeach
  </tbody>
</table>