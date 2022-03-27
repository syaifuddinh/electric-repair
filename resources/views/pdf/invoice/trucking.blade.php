@extends('pdf.invoice.layout')
@section('content')
<div style="margin-top:10%;margin-bottom: 10% ">
	<center><h3>INVOICE<br>{{$item->code}}</h3></center>
	<br>
	<br>
	<table style="width: 100%">
		<tr>
			<td style="width: 10%">To</td>
			<td style="width: 1%">:</td>
			<td style="width: 56%">{{$item->customer->name}}</td>
			<td style="width: 8%">Date</td>
			<td style="width: 5%">:</td>
			<td style="width: 20%">{{Carbon\Carbon::parse($item->date_invoice)->format('d M Y')}}</td>
		</tr>
		<tr>
			<td></td>
			<td></td>
			<td>{{$item->customer->address}}</td>
			<td></td>
			<td></td>
			<td></td>
		</tr>
		<tr>
			<td></td>
			<td></td>
			<td></td>
			<td>No. WO</td>
			<td>:</td>
			<td>{{ @$workOrder->no_bl }}</td>
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
			<td></td>
			<td></td>
			<td></td>
			<td></td>
		</tr>
	</table>
	<br>
		<table style="width: 100%; border-collapse: collapse; border: 1px solid black; border-left: none; border-right: none">
			<tr>
				<th style="width: 5%;"></th>
				<th style="width: 30%;" class="text-center">Description</th>
				<th class="text-center">Qty</th>
				<th class="text-center">Price</th>
				<th class="text-center border-left">AMOUNT IDR</th>
			</tr>
			<?php $grand_total = 0; ?>
			@foreach($details as $key => $detail)
			<?php
				$grand_total += $detail->total_price;
			?>
			<tr>
				 <td class="text-right" style="padding-right:15px;">{{$key+1}}</td>
				 <td>
					 {{$detail->job_order->service->name}} {{$detail->job_order->trayek->from->name}} - {{$detail->job_order->trayek->to->name}}
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
			?>
			@endforeach
			<tr>
				<td style="text-align: right; padding-right:15px;" colspan="1">Total</td>
				<td style="text-align: right;" colspan="3"></td>
				<td style="text-align: right; border:1px solid black; border-bottom: none; border-right: none;">{{formatNumber($grand_total)}}</td>
			</tr>
			<tr>
				<td colspan="4" style="height:42px"> </td>
				<td class="border-left"> </td>
			</tr>
			<tr>
				<td class="text-right" style="padding-right:15px;"> <b>In Word:</b> </td>
				<td colspan="3">
					<p class="text-center">
						<b><i>{{penyebut($grand_total)}} rupiah</i></b>
					</p>
				</td>
				<td class="border-left" colspan="1"></td>
			</tr>
			<tr>
				<td>Enclosed</td>
				<td>:</td>
				<td colspan="2">Original Doc</td>
				<td class="border-left" colspan="1"></td>
			</tr>
			<tr>
				<td>Due Date</td>
				<td>:</td>
				<!-- due_date -->
				<td colspan="2">{{Carbon\Carbon::parse($item->due_date)->format('d M Y')}}</td>
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
	</div>
	@if(!empty($manifests))
	<div class="page-break"></div>
	@include('pdf.invoice.lampiran-trucking')
	@endif
@endsection
