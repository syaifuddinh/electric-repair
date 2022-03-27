@extends('pdf.invoice.layout')
@section('content')
<?php
if(!isset($type_wo)) $type_wo = 1;
if(!isset($lampiran)) $lampiran = [];
?>

<center><h3>INVOICE<br><?php echo $item->code ?></h3></center>
<br>
<br>
<table style="width: 100%" class="font-besar">
	<tr>
		<td style="width: 10%">To</td>
		<td style="width: 1%">:</td>
		<td style="width: 56%;"><?php echo $item->customer->name ?></td>
		<td style="width: 8%">Date</td>
		<td style="width: 1%">:</td>
		<td style="width: 20%"><?php echo Carbon\Carbon::parse($item->date_invoice)->format('d M Y') ?></td>
	</tr>
	<tr>
		<td></td>
		<td></td>
		<td style="padding-right: 100px;"><?php echo $item->customer->address ?></td>
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
<table style="width: 100%; border-collapse: collapse; border: 1px solid black" class="no-horizontal font-besar">
	<thead>
		<tr>
			<th style="border: 1px solid black" >No. Invoice</th>
			<th style="border: 1px solid black" class="text-center"><?php echo $type_wo==1?'No. B/L':'No. PO' ?></th>
			<?php if ($type_wo==1): ?>
				<th style="border: 1px solid black" class="text-center">Custom All</th>
			<?php endif; ?>
			<th style="border: 1px solid black" class="text-center">Trucking</th>
			<th style="border: 1px solid black" class="text-center">Reimburse</th>
			<th style="border: 1px solid black" class="text-center">PPn</th>
			<th style="border: 1px solid black" class="text-center">Total</th>
		</tr>
	</thead>
	<!-- table body -->
	<?php $grandCustomAll=0; $grandPpn=0; $grandTrucking=0; $grandTotal=0;$grandReimburse=0; ?>
	<tbody>
	@foreach($details as $index => $detail)
	<?php
	$grandCustomAll+=$detail->custom_all2;
	$grandPpn+=$detail->total_ppn;
	$grandTrucking+=$detail->trucking2;
	$grandReimburse+=$detail->reimburse;

	if ($type_wo==1) {
		$trucking=$detail->trucking2;
	} else {
		$trucking=$detail->trucking2+$detail->custom_all2;
		$grandCustomAll+=$detail->custom_all2;
	}
	$total = $detail->custom_all2+$detail->trucking2+$detail->total_ppn+$detail->reimburse;
	$grandTotal+=$total;
	?>
		<tr>
			<td><?php echo  $detail->code_invoice  ?></td>
			<td class="text-center"><?php echo  $type_wo==1?$detail->bl:$detail->po_customer  ?></td>
			<?php if ($type_wo==1): ?>
				<td class="text-right"><?php echo  formatNumber($detail->custom_all2)  ?></td>
			<?php endif; ?>
			<td class="text-right"><?php echo  formatNumber($trucking)  ?></td>
			<td class="text-right"><?php echo  formatNumber($detail->reimburse)  ?></td>
			<td class="text-right"><?php echo  formatNumber($detail->total_ppn)  ?></td>
			<td class="text-right"><?php echo  formatNumber($total)  ?></td>
		</tr>
		@endforeach
		<tr>
			<td style="height: 14px;" ></td>
			<?php if ($type_wo==1): ?>
				<td class="text-center"></td>
			<?php endif; ?>
			<td class="text-center"></td>
			<td class="text-center"></td>
			<td class="text-center"></td>
			<td class="text-center"></td>
			<td class="text-center"></td>
		</tr>
		<tr>
			<td style="height: 14px;" ></td>
			<?php if ($type_wo==1): ?>
				<td class="text-center"></td>
			<?php endif; ?>
			<td class="text-center"></td>
			<td class="text-center"></td>
			<td class="text-center"></td>
			<td class="text-center"></td>
			<td class="text-center"></td>
		</tr>
		<?php if ($item->discount_percent > 0 || $item->discount_total > 0): ?>
		<tr>
			<td style="border-top: 1px solid black" class="text-center" colspan="5">Diskon {{ ($item->discount_percent > 0) ? $item->discount_percent.'%' : ''}}</td>
			<?php if ($type_wo==1): ?>
				<td style="border-top: 1px solid black" class="text-right"></td>
			<?php endif; ?>
			 <td style="border-top: 1px solid black" class="text-right border-left">
				 {{ formatNumber($item->discount_total) }}
			 </td>
			</tr>
			<?php $grandTotal -= $item->discount_total; ?>
		<?php endif; ?>
		<tr>
			<td class="text-center font-bold" colspan="5">Total</td>
			<?php if ($type_wo==1): ?>
				<td class="text-right"></td>
			<?php endif; ?>
			<td class="text-right"><?php echo  formatNumber($grandTotal)  ?></td>
		</tr>
	</tbody>
</table>
<br>
<br>
<br>
<br>
<table style="width:100%" class="font-besar">
	<tr>
		<td class="text-left" style="width:10%">
			<b>In Word</b>
		</td>
		<td>
			: <b><i><?php echo penyebut(@$grandTotal) ?> rupiah</i></b>
		</td>
	</tr>
	<tr>
		<td>Enclosed</td>
		<td>: Original Doc</td>
	</tr>
	<tr>
		<td>Due Date</td>
		<td>: <?php echo Carbon\Carbon::parse($item->due_date)->format('d M Y') ?></td>
		<!-- due_date -->
	</tr>
</table>
<br>
<br>
<table style="width: 100%">
	<tr>
		<td style="width: 50%">
			Transfer Payment To<br>
			<b>Rekening</b>
			<!-- Rekening Cabang -->
			<br>
			<br>
			A/N &nbsp;&nbsp; : {{$remark->person}}<br>
			A/C &nbsp;&nbsp; : {{$remark->account}}<br>
			Bank &nbsp;: {{$remark->bank}}<br>
		</td>
		<td style="width: 20%"></td>
		<td style="width: 30%">
			<!-- Cabang Invoice, Tanggal Invoice -->
			{{Carbon\Carbon::parse($item->date_invoice)->format('d M Y')}}<br><br><br><br><br><br><br><br><br>
			{{$remark->signature}}<br>
			<i>{{$remark->position}}</i>
		</td>
	</tr>
</table>
<?php foreach ($lampiran as $lampkey => $lamp): ?>
	<div class="page-break"></div>
	<?php if ($type_wo==1): ?>
		@include('pdf.invoice.wo-gabungan-lampiran')
	<?php else: ?>
		@include('pdf.invoice.wo-gabungan-lampiran2')
	<?php endif; ?>
<?php endforeach; ?>
@endsection
