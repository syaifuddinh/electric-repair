<html>
    <head>
    	<title>Laporan biaya belum transaksi</title>
        <style>
            thead td {
                text-align:center
            }
			tbody td {
				padding:1.4mm;
			}
        </style>
    </head>
    <body>
        <div style='text-align: center;font-weight:bold;font-size:5mm;margin-bottom:8mm'>
            LAPORAN BIAYA BELUM TRANSAKSI
        </div>

        <table style="width:100%" cellspacing='0' border='1'>
            <thead>
                <tr>
                    <td style='width:5mm'>No.</td>
                    <td>Job Order / Manifest</td>
                    <td>Jenis Biaya</td>
                    <td>Keterangan</td>
                    <td style='width:20mm'>Total</td>
                </tr>
            </thead>
            <tbody>
                @foreach ($costs as $key => $cost)
                    <tr>
                        <td>{{ $key + 1 }}.</td>
                        <td>{{ $cost->code }}</td>
                        <td>{{ $cost->name }}</td>
                        <td>{{ $cost->description }}</td>
                        <td style="text-align:right">{{ formatNumber($cost->total_price) }}</td>

                    </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>