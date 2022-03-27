@extends('pdf.invoice.layout')
@section('content')
@include('pdf.invoice.invoice-header')
<br>
<br>
<table style="width: 100%">
	<tr>
		<td style="width: 10%">To</td>
		<td style="width: 1%">:</td>
		<td style="width: 56%">{{$item->customer->name}}</td>
		<td style="width: 8%">Date</td>
		<td style="width: 1%">:</td>
		<td style="width: 20%">{{Carbon\Carbon::parse($item->date_invoice)->format('d M Y')}}</td>
	</tr>
	<tr>
		<td></td>
		<td></td>
		<td style="padding-right: 50px;">{{$item->customer->address}}</td>
		<td>No. WO</td>
		<td>:</td>
		<td>{{ $wo->wo_code }}</td>
	</tr>
	<tr>
		<td>Attn</td>
		<td>:</td>
		<td>Finance</td>
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
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
	</tr>
</table>
<br>
<table style="width: 100%; border-collapse: collapse; border: 1px solid black; border-left: none; border-right: none">
	<tr>
		<th style="width: 10%;" class="border-bottom"></th>
		<th style="width: 30%;" class="text-center border-bottom">Description</th>
		<th class="text-center border-bottom">Qty</th>
		<th class="text-center border-bottom">Price</th>
		<th class="text-center" style="border-left: 1px solid; border-bottom: 1px solid;">AMOUNT IDR</th>
	</tr>
	<?php $grand_total = 0;$ppn=0; ?>
	@foreach($details as $key => $detail)
	<?php
		if($show_ppn != 1 ) {
			$detail->ppn = 0;	
		}
		$grand_total += $detail->total_price + $detail->ppn;
	?>
	<tr>
		<td colspan="4" style="height:10px; border-right:1px solid;"></td>
		<td></td>
	</tr>
	<tr>
		<td class="text-right" style="padding-right:15px;">{{$key+1}}</td>
		<td>
            <?php
            $serviceTypeId = $detail->job_order ? $detail->job_order->service_type_id : 0;
            $hasValidJO = isset($detail->job_order) 
                        && !is_null($detail->job_order);
            ?>
			@if(in_array($serviceTypeId, [1,2,3]) && $hasValidJO)
			{{$detail->job_order->service->name}} {{$detail->job_order->trayek->from->name}} - {{$detail->job_order->trayek->to->name}}
			@elseif($hasValidJO)
			{{$detail->job_order->service->name}}
			@endif
		</td>
		<td class='text-center'>{{$detail->qty}} {{@$detail->job_order->piece->name}}</td>
		<td class="text-center">{{formatNumber($detail->price)}}</td>
		<td class="text-right border-left">
			{{ formatNumber($detail->total_price) }}
		</td>
	</tr>
	<?php
		$manifests = [];
		if(!empty($detail->job_order->detail)){
			foreach($detail->job_order->detail as $jod){
				if (!empty($jod->manifest)) {
					$manifests[] = $jod->manifest;
				}
			}
		}
		if($show_ppn != 1) {
			$detail->ppn = 0;
		}
		$ppn+=$detail->ppn;
	?>
	@endforeach
	@if($show_ppn == 1)
		<tr>
			<td></td>
			<td colspan="2">PPN 10%</td>
			<td class="border-right text-center"></td>
			<td class="text-right">{{formatNumber($ppn)}}</td>
		</tr>
	@endif
	<?php 
	if($item->discount_percent > 0 || $item->discount_total > 0) { 
		$grand_total -= $item->discount_total;
	?>
	<tr>
		<td></td>
		<td colspan="2">Diskon {{ ($item->discount_percent > 0) ? $item->discount_percent.'%' : ''}}</td>
		<td class="border-right text-center"></td>
		<td class="text-right">{{formatNumber($item->discount_total)}}</td>
	</tr>
	<?php } ?>
	<tr>
		<td style="text-align: right; padding-right:15px;" colspan="1">Total</td>
		<td style="text-align: right;" colspan="3"></td>
		<td class="border-top border-left text-right">{{formatNumber($grand_total)}}</td>
	</tr>
	<tr>
		<td colspan="4" style="height:42px"> </td>
		<td class="border-left"> </td>
	</tr>
	<tr>
		<td class="" style="padding-right:15px;"> <b>In Word</b> </td>
		<td colspan="3">: <b><i>{{penyebut($grand_total)}} rupiah</i></b>		</td>
		<td class="border-left" colspan="1"></td>
	</tr>
	<tr>
		<td>Enclosed</td>
		<td>: Original Doc</td>
		<td colspan="2"></td>
		<td class="border-left" colspan="1"></td>
	</tr>
	<tr>
		<td>Due Date</td>
		<td>: {{Carbon\Carbon::parse($item->due_date)->format('d M Y')}}</td>
		<!-- due_date -->
		<td colspan="2"></td>
		<td class="border-left" colspan="1"></td>
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
			A/N &nbsp;&nbsp; : {{ $remark->person }}<br>
			A/C &nbsp;&nbsp; : {{ $remark->account }}<br>
			Bank &nbsp;: {{ $remark->bank }}<br>
		</td>
		<td style="width: 20%"></td>
		<td style="width: 30%">
			<!-- Cabang Invoice, Tanggal Invoice -->
			SURABAYA, {{Carbon\Carbon::parse($item->date_invoice)->format('d M Y')}}<br><br><br><br><br><br><br><br><br>
			{{ $remark->signature }}<br>
			<i>{{ $remark->position }}</i>
		</td>
	</tr>
</table>

@if(!empty($manifests))
<div class="page-break"></div>
@include('pdf.invoice.lampiran-trucking')
@endif
@endsection
