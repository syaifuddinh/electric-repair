@extends('export.layout')

@section('content')
<table style="width: 100%;" class="utama">
  <thead>
    <tr>
      <td width="60%" colspan="5" scope="col"><div align="center"><strong>SOLOG</strong></div>
        <div align="center"><strong>Daftar Akun </strong></div>
      </td>
    </tr>
    <tr>
      <th>Kode</th>
      <th>Nama Akun</th>
      <th>Jenis</th>
      <th>Debet/Kredit</th>
      <th>Group</th>
    </tr>
  </thead>
  <tbody>
    @foreach($data as $value)
    <tr>
      <td>{{$value->code}}</td>
      <td>
        @for($i=0; $i < $value->deep; $i++)
        &nbsp;
        @endfor
        {!! $value->is_base==1?'<strong>'.$value->name.'</strong>':$value->name !!}
      </td>
      <td>{{@$value->type->name}}</td>
      <td>{{$value->jenis_name}}</td>
      <td>{{$value->group_report_name}}</td>
    </tr>
    @endforeach
  </tbody>
</table>
@endsection
