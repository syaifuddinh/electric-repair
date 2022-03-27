@extends('pdf.invoice.layout')
@section('content')
	@foreach($details as $k => $detail)
			@include('pdf.invoice.wo-persatuan-body2')
			<?php if (isset($details[$k+1])): ?>
				<div class="page-break"></div>
			<?php endif; ?>
	@endforeach
@endsection
