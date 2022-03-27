<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        }

        .text-align-left {
            text-align: left;
        }

        #header {
            border: 2px solid black;
            width: 100%;
            height: 76pt;
            margin-top: -25pt;
        }

        .img-logo {
            width: 180px;
            height: 60px;
            margin-top: 14pt;
        }

        .p-kitransnet {
            font-weight: bold;
            font-style: italic;
            font-size: 9pt;
            font-family: Arial, Helvetica, sans-serif;
            margin: -2 0 0 35pt;
        }

        .table-content {
            margin-top: -5pt;
        }

        .header-left {
            width: 40%;
            display: inline-block;
        }

        .header-right {
            width: 60%;
            display: inline-block;
            float: right;
            margin-top: -60pt;
        }

        .header-right p {
            margin: 0;
            text-align: center;
        }

        .p-right-top {
            font-size: 9pt;
            font-weight: bold;
            font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
        }

        .p-right-bottom {
            margin-top: 2pt;
            font-size: 8pt;
            font-family: Arial, Helvetica, sans-serif;
        }

        .p-checklist-kendaraan {
            text-align: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 8pt;
            font-weight: 300;
        }

        table {
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid black;
            padding: 4px;
            /* line-height: 1.2; */
        }

        .ml-50 {
            margin-left: 50pt;
        }

        .mt-10 {
            margin-top: 4pt;
        }

        .mt-15 {
            margin-top: 15px;
        }

        .mt-20 {
            margin-top: 20px;
        }

        table {
            text-align: center;
            font-size: 5.4pt;
        }

        .border-none {
            border: 0px;
            width: 20pt;
        }

        .w-no {
            width: 9pt;
        }

        .w-nopol {
            width: 58pt;
        }

        .w-isi-nopol {
            width: 100pt;
        }

        .font-size-7 {
            font-size: 8pt;
        }

        .ml-29 {
            margin-left: -9pt;
        }

        .font-size-10 {
            font-size: 8px;
        }

        .table-bottom {
            margin-left: 62pt;
        }

        .td-12-pt {
            padding: 1.25pt;
        }

        .color-white {
            color: white;
        }
    </style>
    <style type="text/css" media="print">
        @page {
            size: auto;
            margin: 0;
        }
    </style>
</head>

<body>
    <div id="header">
        <div class="header-left">
            <img src="{{ asset('img/kitrans_grayscale.png') }}" class="img-logo">
            <p class="p-kitransnet">{{$remark->person}}</p>
        </div>

        <div class="header-right">
            <div class="p-right-top">
                <p>{{$remark->person}}</p>
                <p>{{$area->name}}</p>
            </div>

            <div class="p-right-bottom">
                <P>{{$remark->address}}</P>
                <P>Phone : {{$remark->phone}}</P>
                <P>email : <i>{{$remark->email}}</i></P>
            </div>
        </div>
    </div>

    <p class="p-checklist-kendaraan">CHECKLIST KENDARAAN</p>

    <div class="table-content">
        <span class="font-size-7 ml-29">Area : {{ $area->name }}</span>
        <span class="ml-50 font-size-7">Tahun : {{ substr($vehicle_checklist_items[0]->date_transaction,0,4) }}</span>

        <table class="mt-10 ml-29 font-size-10">
            <tr>
                <th>NO</th>
                <th colspan="5">DATA KENDARAAN</th>
                <th class="border-none"></th>
            </tr>

            <tr>
                <td>1</td>
                <td class="text-align-left w-nopol">NO. POLISI :</td>
                <td class="w-isi-nopol">{{ $vehicle->nopol }}</td>
                <td class="w-no">4</td>
                <td class="text-align-left w-nopol">SERVIS RECORD :</td>
                <td class="w-isi-nopol"> </td>
                <td class="border-none"> </td>
                <td >NAMA DRIVER :</td>
                <td class="border-none"> </td>
                <td >KETERANGAN :</td>
            </tr>

            <tr>
                <td>2</td>
                <td class="text-align-left">TYPE <span class="tab-2"></span>:</td>
                <td>{{ $vehicle_variant->name }}</td>
                <td>5</td>
                <td class="text-align-left">BAHAN BAKAR <span class="tab-5"></span>:</td>
                <td>{{ strtoupper($bbm_type->name) }}</td>
                <td class="border-none"> </td>
                <td rowspan="2">
                    <?php
                        foreach($drivers as $driver)
                        {
                            echo $driver->name;
                        }
                    ?>
                </td>
                <td class="border-none"> </td>
                <td>A = ADA</td>
            </tr>

            <tr>
                <td>3</td>
                <td class="text-align-left">TAHUN <span class="tab-3"></span>:</td>
                <td>{{ $vehicle_variant->year_manufacture }}</td>
                <td>6</td>
                <td class="text-align-left">KILOMETER <span class="tab-6"></span>:</td>
                <td>{{ $vehicle->last_km }}</td>
                <td class="border-none"> </td>
                <td class="border-none"> </td>
                <td>T = TIDAK ADA</td>
            </tr>
        </table>

        <table class="mt-15 ml-29">
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2" class="w">PERLENGKAPAN</th>
                <th colspan="31">{{ strtoupper(date("F", mktime(0, 0, 0, substr($vehicle_checklist_items[0]->date_transaction,5,2), 10))) }}</th>
            </tr>

            <tr>
                <?php
                for($i=1; $i<=31; $i++){
                    ?>
                    <td>
                        <?php echo $i; ?>
                    </td>
                    <?php
                }
                ?>
            </tr>

            <?php
                $nomor = 1;
                foreach ($vehicle_checklists as $data){
                    $mark = "";
                    $start = true;
                    ?>
                    <tr>
                        <td>{{ $nomor }}</td>
                        <td class="text-align-left">{{ $data->name }}</td>
                        <?php
                        // looping pengecekan tiap tanggal
                        for($i=1; $i<=31; $i++){
                            $ada_pengecekan = false;
                            ?>
                            <td class="td-12-pt">
                                <?php
                                    //untuk semua list checklist items
                                    foreach($vehicle_checklist_items as $v){
                                        // apakah ada tgl pengecekan yg sama dgn tgl pada form
                                        if(substr($v->date_transaction,8) == $i){
                                            $ada_pengecekan = true;
                                            $start = false;
                                            // apakah id/nama data nya sama dgn yg ada pada form
                                            if($v->vehicle_checklist_id == $data->id){
                                                // jika kondisi ada
                                                if($v->condition == 1){
                                                    echo "<b>A</b>";
                                                    $mark = "A";
                                                }
                                                // jika kondisi tidak ada
                                                else {
                                                    echo "<b>T</b>";
                                                    $mark = "T";
                                                }
                                            }
                                        }
                                    }
                                    // jika tidak ada pengecekan dari looping awal
                                    if(!$ada_pengecekan && $start){
                                        echo "<span class=\"color-white\">T</span>";
                                    }
                                    // jika tidak ada pengecekan namun sebelumnya sudah ada pengecekan
                                    else if(!$ada_pengecekan && !$start){
                                        echo $mark;
                                    }
                                ?>
                            </td>
                            <?php
                        }
                        ?>
                    </tr>
                    <?php
                    $nomor++;
                }

                foreach ($vehicle_bodies as $data){
                    $mark = "";
                    $start = true;
                    ?>
                    <tr>
                        <td>{{ $nomor }}</td>
                        <td class="text-align-left">{{ $data->name }}</td>
                        <?php
                        for($i=1; $i<=31; $i++){
                            $ada_pengecekan = false;
                            ?>
                            <td class="td-12-pt">
                                <?php
                                    // looping semua data checklist bodies
                                    foreach($vehicle_checklist_bodies as $v){
                                        // jika tgl data pengecekan sama dengan tgl pada form
                                        if(substr($v->date_transaction,8) == $i){
                                            $ada_pengecekan = true;
                                            $start = false;
                                            // jika id/nama part sama dengan yang ada pada form
                                            if($v->vehicle_body_id == $data->id){
                                                if($v->condition == 1){
                                                    echo "<b>A</b>";
                                                    $mark = "A";
                                                }
                                                else {
                                                    echo "<b>T</b>";
                                                    $mark = "T";
                                                }
                                            }
                                        }
                                    }
                                    if(!$ada_pengecekan && $start){
                                        echo "<span class=\"color-white\">T</span>";
                                    }
                                    else if(!$ada_pengecekan && !$start){
                                        echo $mark;
                                    }
                                ?>
                            </td>
                            <?php
                        }
                        ?>
                    </tr>
                    <?php
                    $nomor++;
                }
            ?>

        </table>
    </div>

    <div class="table-bottom" align="right">
        <table class="mt-20">
            <tr>
                <td>TT PEMERIKSA</td>
                <?php for($i=1; $i<=31; $i++){
                    ?>
                    <td class="color-white">
                        {{ $i }}
                    </td>
                    <?php
                }
                ?>
            </tr>

            <tr>
                <td>TT DRIVER</td>
                <?php for($i=1; $i<=31; $i++){
                    ?>
                    <td>

                    </td>
                    <?php
                }
                ?>
            </tr>
        </table>
    </div>
</body>

</html>
<script>
    // window.print();
</script>
