@extends('pdf.invoice.layout')
@section('content')
	@include('pdf.invoice.invoice-header')
	<br>
	<div class="row">
		<div class="column">
			<table style="width: 100%" class="text-vertical">
				<tr>
					<td style="width: 20%">To</td>
					<td style="width: 1%">:</td>
					<td style="width: 70%"><?php echo $item->customer->name?></td>
				</tr>
				<tr>
					<td></td>
					<td></td>
					<td><?php echo $item->customer->address?></td>
				</tr>
				<tr>
					<td>Attn</td>
					<td>:</td>
					<td>Finance</td>
				</tr>
			</table>

		</div>
		<div class="column">
			<table style="width: 100%" class="text-vertical">
				<tr>
					<td style="width: 20%">Date</td>
					<td style="width: 1%">:</td>
					<td><?php echo Carbon\Carbon::parse($item->date_invoice)->format('d M Y')?></td>
				</tr>
				<tr>
					<td>No. WO</td>
					<td>:</td>
					<td>{!! @$details[0]->code_wo !!}</td>
				</tr>
				<tr>
					<td>No. PO</td>
					<td>:</td>
					<td> <?php echo ($po->po_customer ?? "") ?></td>
				</tr>

				<?php foreach ($details as $key => $value): ?>
					<tr>
						<td>PARTY</td>
						<td>:</td>
						<td>
							<?php
							$party="";
							foreach ($details as $xs) {
								echo '- '.$xs->qty.' x '.$xs->name.'<br>';
							} ?>
						</td>
					</tr>

					<?php break; ?>
				<?php endforeach; ?>
			</table>

		</div>
	</div>
	<br>
		<table style="width: 100%; border-collapse: collapse; border: 1px solid black; border-left: none; border-right: none">
			<tr>
				<th style="width: 10%;" class="border-bottom"></th>
				<th style="width: 30%;" class="text-center border-bottom">Description</th>
				<th class="text-center border-bottom">Qty</th>
				<th class="text-center border-bottom">Price</th>
				<th class="text-center border-bottom">AMOUNT IDR</th>
			</tr>
			<tr>
				<td colspan="4" class="border-right" style="height:10px;"></td>
				<td></td>
			</tr>
			<?php $grand_total = 0;$ppn=0; ?>
			@foreach($details as $key => $detail)
			<?php
				$grand_total += $detail->total_price;
				if($show_ppn == 1) {
					$ppn+=$detail->ppn;
				}
			?>
			<tr>
				 <td class="text-right" style="padding-right:15px; vertical-align:top;"><?php echo $key+1?></td>
				 <td class="vertical-align:top;">
					 <?php echo $detail->trayek ?>
				 </td>
				 <td class='text-center' style="vertical-align:top;"><?php echo $detail->qty.' x '.$detail->name ?></td>
				 <td class="text-right" style="vertical-align:top;"><?php echo formatNumber($detail->price)?></td>
				 <td class="text-right border-left" style="vertical-align:top;">
					 <?php echo  formatNumber($detail->total_price) ?>
				 </td>
			</tr>
			@endforeach
			<?php foreach ($additional as $key => $value): ?>
				<tr>
					 <td class="text-right" style="padding-right:15px;"></td>
					 <td style="vertical-align:top;">
						 <?php echo $value->name?>
					 </td>
					 <td class='text-center'></td>
					 <td class="text-right"></td>
					 <td class="text-right border-left" style="vertical-align:top;">
						 <?php echo  formatNumber($value->total_price) ?>
					 </td>
				</tr>
				<?php $grand_total+=$value->total_price; ?>
			<?php endforeach; ?>
			<?php if ($ppn>0): ?>
				@if($show_ppn == 1)
					<tr>
						 <td class="text-right" style="padding-right:15px;"></td>
						 <td>
							 PPN 10%
						 </td>
						 <td class='text-center'></td>
						 <td class="text-center"></td>
						 <td class="text-right border-left">
							 <?php echo  formatNumber($ppn) ?>
						 </td>
					</tr>
				@endif
			<?php endif; ?>
			<?php if ($tax->amount>0): ?>
				<tr>
					 <td class="text-right" style="padding-right:15px; height:20px;"></td>
					 <td>
						 <?php echo $tax->name?>
					 </td>
					 <td class='text-center'></td>
					 <td class="text-center"></td>
					 <td class="text-right border-left">
						 <?php echo  formatNumber($tax->amount) ?>
					 </td>
				</tr>
				<?php $ppn+=$tax->amount; ?>
			<?php endif; ?>
			<?php if ($item->discount_percent > 0 || $item->discount_total > 0): ?>
				<tr>
					 <td class="text-right" style="padding-right:15px; height:20px;"></td>
					 <td>Diskon {{ ($item->discount_percent > 0) ? $item->discount_percent.'%' : ''}}</td>
					 <td class='text-center'></td>
					 <td class="text-center"></td>
					 <td class="text-right border-left">
						 {{ formatNumber($item->discount_total) }}
					 </td>
				</tr>
				<?php $grand_total -= $item->discount_total; ?>
			<?php endif; ?>
			<tr>
				<td style="text-align: right; padding-right:15px; height: 20px;" class="font-bold" colspan="1"></td>
				<td style="text-align: left;" colspan="3" class="font-bold">Total</td>
				<td style="border:1px solid black; border-bottom: none; border-right: none;" class="text-right"><?php echo formatNumber($grand_total+$ppn)?></td>
			</tr>
			<tr>
				<td colspan="4" style="height:20px"> </td>
				<td class="border-left"> </td>
			</tr>
			<tr>
				<td class="text-right" > <b>In Word</b> </td>
				<td colspan="3">
					<b>: <i><?php echo penyebut($grand_total+$ppn)?> rupiah</i></b>
				</td>
				<td class="border-left" colspan="1"></td>
			</tr>
			<tr>
				<td class="text-right">Enclosed</td>
				<td colspan="2">: Original Doc</td>
				<td></td>
				<td class="border-left" colspan="1"></td>
			</tr>
			<tr>
				<td class="text-right">Due Date</td>
				<td colspan="2">: <?php echo Carbon\Carbon::parse($item->due_date)->format('d M Y')?></td>
				<!-- due_date -->
				<td></td>
				<td class="border-left" colspan="1"></td>
			</tr>
		</table>
		<br>
		<div class="bottom-page">
			<table style="width: 100%">
				<tr>
					<td style="width: 50%;vertical-align:top;">
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
					<td style="width: 30%;vertical-align:top;">
						<!-- Cabang Invoice, Tanggal Invoice -->
						SURABAYA, {{Carbon\Carbon::parse($item->date_invoice)->format('d M Y')}}<br><br><br><br><br><br><br><br><br>
						{{ $remark->signature }}<br>
						<i>{{ $remark->position }}</i>
					</td>
				</tr>
			</table>
		</div>
	@if(count($manifests)>0)
	<div class="page-break"></div>
	@include('pdf.invoice.lampiran-inter-island-rev')
	@endif
@endsection
