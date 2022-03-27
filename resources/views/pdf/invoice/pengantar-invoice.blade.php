<!DOCTYPE html>
<html>
<head>
<title>Cetak Invoice</title>
<style media="screen">
	body {
		font-family: Arial;font-size:11px;
	}
	.text-right {
		text-align: right;
		padding-right: 5px;
	}
	.text-top tr td {
		vertical-align: top;
	}
		/* Create two equal columns that floats next to each other */
	.column {
	  float: left;
	  width: 50%;
	  padding: 10px;
	}

	/* Clear floats after the columns */
	.row:after {
	  content: "";
	  display: table;
	  clear: both;
	}
	.text-center {
		text-align: center;
	}
	.font-bold {
		font-weight: bold;
	}
	.border-left{
	  border-left: 1px solid black;
	}
	.border-bottom{
		border-bottom: 1px solid black;
	}
	.border-top{
		border-top: 1px solid black;
	}
	.border-right{
		border-right: 1px solid black;
	}
	.page-break {
	  page-break-after: always;
	}
	.content {
		margin-top: 5%;
		margin-bottom: 10%;
	}
	.header, .header-space,
	.footer, .footer-space {
	  height: 100px;
	}

	.header {
	  position: fixed;
	  top: 0;
	}

	.footer {
	  position: fixed;
	  bottom: 0;
	}
	table.no-horizontal tbody tr td {
		border-left: 1px solid;
		border-right: 1px solid;
	}
	table.text-vertical tr td {
		vertical-align: top;
	}
	table.no-horizontal tbody tr:last-child td {
		border-bottom: 1px solid;
	}
	table.no-horizontal tbody tr:first-child td {
		border-top: 1px solid;
	}

</style>
</head>
<body>
    <div style="position:fixed; top:0; padding:0; margin:0">
    <table style="width: 100%;">
        <tr>
            <td style="width: 95%"></td>
            <td style="border: solid 3px #060;padding: 2px 3px">
                    {{ $bill['isCopy'] }}</tr>
    </table>
    <div class="content">
        <table style="width: 100%">
            <tr>
                <td style="width: 70%"></td>
                <td>{{$remark->person}}</td>
            </tr>
            <tr>
                <td></td>
                <td>{{$remark->address}}</td>
            </tr>
            <tr>
                <td></td>
                <td>Phone : {{$remark->phone}} Fax : {{$remark->fax}}</td>
            </tr>
            <tr>
                <td></td>
                <td>email: {{$remark->email}}</td>
            </tr>
        </table>

        <center><h1>PENGANTAR INVOICE<br></h1></center>
        <br>
        <br>
        <table style="width: 100%">
            <tr>
                <td style="width: 10%">To</td>
                <td style="width: 1%">:</td>
                <td style="width: 56%;">{{ $bill['customer_name'] }}</td>
                <td style="width: 8%">Date</td>
                <td style="width: 1%">:</td>
                <td style="width: 20%">{{ Carbon\Carbon::now()->format('d M Y') }}</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td style="padding-right: 100px;">{{ $bill['customer_address'] }}</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>Attn</td>
                <td>:</td>
                <td>Finance</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </table>
        <br>
        <table style="width: 100%; border-collapse: collapse; border: 1px solid black" class="no-horizontal">
            <thead>
                <tr>
                    <th style="border: 1px solid black" class="text-center">NO</th>
                    <th style="border: 1px solid black" class="text-center">NO INVOICE</th>
                    <th style="border: 1px solid black" class="text-center">LAMPIRAN</th>
                    <!--th style="border: 1px solid black" class="text-center">KAPAL</th-->
                    <th style="border: 1px solid black" class="text-center">KETERANGAN</th>
                    <th style="border: 1px solid black" class="text-center">TOTAL</th>
                </tr>
            </thead>
            <?php
                $grandTotal = 0;
            ?>
            <tbody>
                @foreach($details as $index => $detail)
                <?php
                    $grandTotal += $detail['total'];
                ?>
                <tr>
                    <td class="text-center">{{ ($index + 1) }}</td>
                    <td class="text-center">{{ $detail['code'] }}</td>
                    <td class="text-left">{{ $detail['lampiran'] }}</td>
                    <!--td class="text-left">{{ $detail['kapal'] }}</td-->
                    <td class="text-left">{{ $detail['description'] }}</td>
                    <td class="text-right">{{ formatNumber($detail['total']) }}</td>
                </tr>
                @endforeach
                <tr>
                    <td style="border: 1px solid black" colspan="2" class="text-center">
                        <font size="2">
                            <strong>Jatuh Tempo : {{Carbon\Carbon::parse($bill['due_date'])->format('d F Y')}}</strong>
                        </font>
                    </td>
                    <td style="border: 1px solid black" colspan="2" class="text-center">Total</td>
                    <td style="border: 1px solid black"
                        class="text-right">{{formatNumber($grandTotal)}}</td>
                </tr>
            </tbody>
        </table>
        <br>
        <br>
        <p>Keuangan bisa ditransfer ke rekening kami:</p>
        <table style="width: 100%">
            <tr><td><strong>{{$remark->bank}}</strong></td></tr>
            <tr><td><strong>AC NO. {{$remark->account}}</strong></td></tr>
            <tr><td>atas nama: <strong>{{$remark->person}}</strong></td></tr>
        </table>
        <p>Demikian penyampaian dari kami, atas perhatian dan kerjasamanya kami ucapkan terima kasih.</p>
        <br>
        <table style="width: 100%">
            <tr>
                <td style="width: 30%" class="text-center">Hormat kami,</td>
                <td style="width: 40%"></td>
                <td style="width: 30%" class="text-center">Penerima,</td>
            </tr>
            <tr><td> </td><td> </td><td> </td></tr>
            <tr><td> </td><td> </td><td> </td></tr>
            <tr><td> </td><td> </td><td> </td></tr>
            <tr><td> </td><td> </td><td> </td></tr>
            <tr><td> </td><td> </td><td> </td></tr>
            <tr><td> </td><td> </td><td> </td></tr>
            <tr><td> </td><td> </td><td> </td></tr>
            <tr><td> </td><td> </td><td> </td></tr>
            <tr>
                <td class="text-center">({{$remark->signature}})</td>
                <td></td>
                <td class="text-center">(Nama, TTD, Sampel)</td>
            </tr>
        </table>
    </div>
</body>
</html>
