<div class="row">
    <div class="column" style='width:25%'>
        @if($remark->logo)
        <img src="data:image/jpeg;base64,{{ base64_encode(@file_get_contents(url('/files/'.$remark->logo))) }}" style='max-width:30mm;max-height:30mm;height:auto'>
        @endif
    </div>
    <div class="column" style='width:40%;font-size:80%'>
        <span style='display:inline-block;margin-bottom:1mm'>
            <b style='text-transform:uppercase'>
                {{ $remark->person }}
            </b>
        </span>
        <br>
        <span>{{ $remark->address }}</span>
        <span>Telp : {{ $remark->phone }} (hunting) Fax : {{ $remark->fax }}</span>
        <br>
        <span>SMS Center : {{ $remark->sms_center }}</span>
        <br>
        <span>
            <span>
                Email :
            </span>
            <span style='font-style: italic;color:orange;font-weight:bold'>
                {{ $remark->email }}
            </span>
        </span>
    </div>

    <div class="column" style='width:35%;'>
        <center>
            <h3 style='margin:0;padding-bottom:0.5mm'>INVOICE</h3>
            <h5 style='margin:0;display:inline-block;padding-top:0.5mm;border-top:1px solid black'>No : {{$item->code}}</h5>
        </center>
    </div>
</div>
