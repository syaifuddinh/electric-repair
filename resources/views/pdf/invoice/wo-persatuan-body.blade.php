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
				<th style="width: 10%;"></th>
				<th style="width: 25%;" class="text-center">Description</th>
				<th class="text-center">Qty</th>
				<th class="text-center">PPn</th>
				<th class="text-center">Price</th>
				<th class="text-center border-left">AMOUNT IDR</th>
			</tr>
			<!-- freight forwading = (service_type_id != 3), (trucking = 3 & container) -->
			<?php
				$grand_total = 0;
				$grand_total_ff = 0;
				$grand_total_trucking = 0;

				$freightForwardingSerivces = [];
				$truckingServices = [];
				foreach ($services as $key => $service) {
					if (!in_array($service->service_type_id, [1,2,3])) {
						$freightForwardingSerivces[] = $service;
					}else {
						$truckingSerivces[] = $service;
					}
				}
			?>
			<tr>
				<td class="text-center">A</td>
				<td>Freight Forwarding</td>
				<td class='text-right'></td>
				<td class="text-right"></td>
				<td class="text-right"></td>
				<td class="text-right border-left"></td>
			</tr>
			@foreach($freightForwardingSerivces as $key => $freightForwardingSerivce)
			<?php
			$tax=App\Model\InvoiceTax::where('invoice_detail_id', $detail->id)->sum('amount');

		 $ppn=App\Model\InvoiceDetail::where('header_id', $item->id)->where('job_order_id', $freightForwardingSerivce->id)->pluck('ppn')->first();

			$total_price = $tax + $ppn + $detail->total_price;
			$grand_total_ff += $total_price;
			?>
			<tr>
				 <td class="text-right" style="padding-right:15px;">{{$key+1}}</td>
				 <td>{{$freightForwardingSerivce->service->name}}</td>
				 <td class='text-center'>{{$detail->qty}} {{@$detail->job_order->piece->name}}</td>
				 <td class='text-center'>{{@formatNumber($ppn)}}</td>
				 <td class="text-center">{{formatNumber($detail->price)}}</td>
				 <td class="text-right border-left">
					 {{ formatNumber($total_price) }}
				 </td>
			</tr>
			@endforeach
			<tr>
				<td style="text-align: right; padding-right:15px;" colspan="1">Total FF</td>
				<td style="text-align: right;" colspan="4"></td>
				<td style="text-align: right; border:1px solid black; border-bottom: none; border-right: none;">{{formatNumber($grand_total_ff)}}</td>
			</tr>
			@if(!empty($truckingSerivces))
			<tr>
				<td class="text-center">B</td>
				<td>Trucking</td>
				<td class='text-right'></td>
				<td></td>
				<td class="text-right"></td>
				<td class="text-right border-left"></td>
			</tr>
			@foreach($truckingSerivces as $key => $truckingSerivce)
			<tr>
				<?php
				$tax=App\Model\InvoiceTax::where('invoice_detail_id', $detail->id)->sum('amount');

				$ppn=App\Model\InvoiceDetail::where('header_id', $item->id)->where('job_order_id', $truckingSerivce->id)->pluck('ppn')->first();
				$total_price = $tax + $ppn + $truckingSerivce->total_price;
				$grand_total_trucking += $total_price;
				?>
				 <td class="text-right" style="padding-right:15px;">{{$key+1}}</td>
				 <td>{{$truckingSerivce->service->name}}<br>{{$truckingSerivce->trayek->from->name}} to {{$truckingSerivce->trayek->to->name}}</td>
				 <td class='text-center'>{{$detail->qty}} {{@$truckingSerivce->piece->name}}</td>
				 <td class='text-center'>{{@formatNumber($ppn)}}</td>
				 <td class="text-center">{{formatNumber($truckingSerivce->total_price)}}</td>
				 <td class="text-right border-left">{{ formatNumber($total_price) }}</td>
			</tr>
			@endforeach
			<tr>
				<td style="text-align: right; padding-right:15px;" colspan="1">Total Trucking</td>
				<td style="text-align: right;" colspan="4"></td>
				<td style="text-align: right; border:1px solid black; border-bottom: none; border-right: none;">{{formatNumber($grand_total_trucking)}}</td>
			</tr>
			@endif
			<?php $grand_total = $grand_total_ff+$grand_total_trucking; ?>
			<tr>
				<td style="text-align: right; padding-right:15px;" colspan="1">Total</td>
				<td style="text-align: right;" colspan="4"></td>
				<td style="text-align: right; border:1px solid black; border-bottom: none; border-right: none;">{{formatNumber($grand_total)}}</td>
			</tr>
			<tr>
				<td colspan="5" style="height:42px"> </td>
				<td class="border-left"> </td>
			</tr>
			<tr>
				<td class="text-right" style="padding-right:15px;"> <b>In Word:</b> </td>
				<td colspan="4">
					<p class="text-center">
						<?php $grand_total = $grand_total_ff + $grand_total_trucking; ?>
						<b><i>{{penyebut($grand_total)}} rupiah</i></b>
					</p>
				</td>
				<td class="border-left" colspan="1"></td>
			</tr>
			<tr>
				<td>Enclosed</td>
				<td>:</td>
				<td colspan="3">Original Doc</td>
				<td class="border-left" colspan="1"></td>
			</tr>
			<tr>
				<td>Due Date</td>
				<td>:</td>
				<!-- due_date -->
				<td colspan="3">{{Carbon\Carbon::parse($item->due_date)->format('d M Y')}}</td>
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
@endsection
