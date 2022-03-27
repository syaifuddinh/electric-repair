app.controller('vehicleVehicle', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle = $rootScope.solog.label.vehicle.title;
    $('.ibox-content').addClass('sk-loading');
    $scope.formData = {};
    $scope.is_filter = false;

    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        dom: 'Blfrtip',
        buttons: [{
            extend: 'excel',
            enabled: true,
            action: newExportAction,
            text: '<span class="fa fa-file-excel-o"></span> Export Excel',
            className: 'btn btn-default btn-sm pull-right m-l-sm ',
            filename: 'Vehicle - List Kendaraan - ' + new Date,
            sheetName: 'Data',
            title: 'Vehicle - List Kendaraan',
            exportOptions: {
            rows: {
                selected: true
            }
            },
        }],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/vehicle/vehicle_datatable',
            data: function(d) {
                d.company_id = $scope.formData.company_id;
                d.vehicle_type_id = $scope.formData.vehicle_type_id;
                d.is_internal = $scope.formData.is_internal;
            },
            dataSrc: function(d) {
                $('.ibox-content').removeClass('sk-loading');
                return d.data;
            }
        },
        columns:[
            {data:"code",name:"code"},
            {data:"nopol",name:"vehicles.nopol"},
            {data:"vehicle_variant.name",name:"vehicle_variant.name"},
            {data:"vehicle_variant.vehicle_type.name",name:"vehicle_variant.vehicle_type.name"},
            {data:"supplier.name",name:"supplier.name"},
            {data:"company.area.name",name:"company.area.name"},
            {data:"company.name",name:"company.name"},
            {data:"last_km",name:"last_km"},
            {data:"action",name:"created_at", sorting:false, className : 'text-center'},
        ],
        createdRow: function(row, data, dataIndex) {
            if($rootScope.roleList.includes('vehicle.vehicle.detail')) {
                $(row).find('td').attr('ui-sref', 'vehicle.vehicle.show({id:' + data.id + '})')
                $(row).find('td:last-child').removeAttr('ui-sref')
            } else {
                $(oTable.table().node()).removeClass('table-hover')
            }
            $compile(angular.element(row).contents())($scope);
        }
    });

    oTable.buttons().container().appendTo('.ibox-tools')

    $scope.deletes=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/vehicle/vehicle/'+ids,{_token:csrfToken}).then(function success(data) {
                oTable.ajax.reload();
                toastr.success("Data Berhasil Dihapus!");
            }, function error(data) {
                toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
            });
        }
    }

    $scope.refreshTable=function() {
        oTable.ajax.reload();
    }

    $scope.reset_filter=function() {
        $scope.formData={};
        $scope.refreshTable()
    }

    $scope.exportExcel = function() {
        var paramsObj = oTable.ajax.params();
        var params = $.param(paramsObj);
        var url = baseUrl + '/excel/semua_kendaraan_export?';
        url += params;
        location.href = url;
    }
});

app.controller('vehicleVehicleCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Tambah Kendaraan";

    $scope.formData={};
    $scope.formData.company_id=compId;
    $scope.formData.is_trailer=0;
    $scope.formData.is_active=1;
    $scope.formData.initial_km=0;
    $scope.formData.last_km=0;
    $scope.formData.daily_distance=0;
    $scope.formData.is_internal=1;

    $http.get(baseUrl+'/vehicle/vehicle/create').then(function(data) {
        $scope.data=data.data;
    });

    $scope.isTrailerChange=function() {
        $scope.formData.max_tonase=0
        $scope.formData.max_volume=0
        $scope.formData.trailer_size=20
    }

    $scope.trailer_size=[
    {id:20,name:"20 Ft"},
    {id:40,name:"40 Ft"},
    ];

    $scope.backward = function() {
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
          $scope.emptyBuffer()
          $state.go('vehicle.vehicle')
        }
    }

    $scope.disBtn=false;
    $scope.submitForm=function() {
        $scope.disBtn=true;
        $scope.formError={}
        $http.post(baseUrl+'/vehicle/vehicle',$scope.formData).then(function(data) {
            $timeout(function() {
                if($rootScope.hasBuffer()){
                    $rootScope.accessBuffer()
                } else {
                    $state.go('vehicle.vehicle');
                }
            },1000)
            toastr.success("Data Berhasil Disimpan !");
            $scope.disBtn=false;
        }, function(error) {
            $scope.disBtn=false;
            if (error.status==422) {
                $scope.formError=error.data.errors
            } else {
                toastr.error(error.data.message,"Error Has Found !");
            }
        });

    }

});
app.controller('vehicleVehicleEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Edit Kendaraan";
    $scope.formData={};
    $http.get(baseUrl+'/vehicle/vehicle/'+$stateParams.id+'/edit').then(function(data) {
        $scope.data=data.data;
        var dt=data.data.item;
        $scope.formData.company_id=dt.company_id;
        $scope.formData.code=dt.code;
        $scope.formData.nopol=dt.nopol;
        $scope.formData.vehicle_variant_id=dt.vehicle_variant_id;
        $scope.formData.vehicle_owner_id=dt.vehicle_owner_id;
        $scope.formData.year_manufacture=dt.year_manufacture;
        $scope.formData.chassis_no=dt.chassis_no;
        $scope.formData.machine_no=dt.machine_no;
        $scope.formData.color=dt.color;
        $scope.formData.supplier_id=dt.supplier_id;
        $scope.formData.is_active=dt.is_active;
        $scope.formData.not_active_reason=dt.not_active_reason;
        $scope.formData.stnk_no=dt.stnk_no;
        $scope.formData.stnk_name=dt.stnk_name;
        $scope.formData.stnk_address=dt.stnk_address;
        $scope.formData.bpkb_no=dt.bpkb_no;
        $scope.formData.initial_km=dt.initial_km;
        $scope.formData.last_km=dt.last_km;
        $scope.formData.daily_distance=dt.daily_distance;
        $scope.formData.gps_no=dt.gps_no;
        $scope.formData.serep_tire=dt.serep_tire;
        $scope.formData.is_trailer=dt.is_trailer;
        $scope.formData.trailer_size=dt.trailer_size;
        $scope.formData.max_tonase=dt.max_tonase;
        $scope.formData.max_volume=dt.max_volume;
        $scope.formData.is_internal=dt.is_internal;
        if (dt.date_manufacture!=null) {
            $scope.formData.date_manufacture=$filter('minDate')(dt.date_manufacture);
        }
        if (dt.date_operation!=null) {
            $scope.formData.date_operation=$filter('minDate')(dt.date_operation);
        }
        if (dt.stnk_date!=null) {
            $scope.formData.stnk_date=$filter('minDate')(dt.stnk_date);
        }
        if (dt.kir_date!=null) {
            $scope.formData.kir_date=$filter('minDate')(dt.kir_date);
        }
        if (dt.initial_km_date!=null) {
            $scope.formData.initial_km_date=$filter('minDate')(dt.initial_km_date);
        }
        if (dt.last_km_date!=null) {
            $scope.formData.last_km_date=$filter('minDate')(dt.last_km_date);
        }
    });

    $scope.isTrailerChange=function() {
        $scope.formData.max_tonase=0
        $scope.formData.max_volume=0
        $scope.formData.trailer_size=20
    }

    $scope.trailer_size=[
    {id:20,name:"20 Ft"},
    {id:40,name:"40 Ft"},
    ];

    $scope.disBtn=false;
    $scope.submitForm=function() {
        $scope.disBtn=true;
        $scope.formError={}
        $http.put(baseUrl+'/vehicle/vehicle/'+$stateParams.id,$scope.formData).then(function(data) {
            $timeout(function() {
                $state.go('vehicle.vehicle');
            },1000)
            toastr.success("Data Berhasil Disimpan !");
            $scope.disBtn=false;
        }, function(error) {
            $scope.disBtn=false;
            if (error.status==422) {
                $scope.formError=error.data.errors
            } else {
                toastr.error(error.data.message,"Error Has Found !");
            }
        });

    }


});
app.controller('vehicleVehicleShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Info Kendaraan";
    if ($state.current.name=="vehicle.vehicle.show") {
        $state.go('vehicle.vehicle.show.card');
    }
});
app.controller('vehicleVehicleShowCard', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Info Kendaraan | Kartu";

    $scope.is_active=[
    {id:1,name:'<span class="badge badge-primary">AKTIF</span>'},
    {id:0,name:'<span class="badge badge-danger">TIDAK AKTIF</span>'},
    ]

    $scope.showPrint = function()
    {
        // url belum ada, passing id vehicle dan value date atau bulan
        // buat routing di laravel dulu, web.php, trus passing variabel 
        // $stateParams.id dan $scope.date
        console.log($stateParams.id);
        console.log($scope.date);
        window.open(baseUrl + '/vehicle/vehicle/print/' + $stateParams.id + '/' + $scope.date);
    }

    oTable = $('#datatable').DataTable({
        processing:true,
        serverSide:true,
        ajax:{
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url:baseUrl+'/api/operational/delivery_order_driver_datatable',
            data:{vehicle_id:$stateParams.id}
        }, columns:[
        {data:'code',name:'code'},
        {data:'code_pl',name:'manifests.code'},
        {data:'driver',name:'driver.name'},
        {data:'trayek',name:'routes.name'},
        {data:'status_name',name:'job_statuses.name'},
        {data:'is_finish',name:'is_finish',className:'text-center'},
        {data:'action',name:'created_at',className:'text-center'},
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }

    })

    $http.get(baseUrl+'/vehicle/vehicle/card/'+$stateParams.id).then(function(data) {
        $scope.data=data.data
        $scope.item=data.data.item
    })
});
app.controller('vehicleVehicleShowDetail', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Info Kendaraan | Detail";
    if ($state.current.name=="vehicle.vehicle.show.detail") {
        $state.go('vehicle.vehicle.show.detail.detail');
    }

});
app.controller('vehicleVehicleShowMaintenance', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Info Kendaraan | Perawatan";
    $scope.tot={};
    $http.get(baseUrl+'/api/vehicle/hitung_perawatan/'+$stateParams.id).then(function(data) {

$scope.tot.pengajuan=data.data.pengajuan;
$scope.tot.rencana=data.data.rencana;
$scope.tot.perawatan=data.data.perawatan;
$scope.tot.selesai=data.data.selesai;
});
    if ($state.current.name=="vehicle.vehicle.show.maintenance") {
        $state.go('vehicle.vehicle.show.maintenance.pengajuan');
    }
});
app.controller('vehicleVehicleShowMaintenanceCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Info Kendaraan | Perawatan - Tambah";
    $scope.formData={};
    $scope.formData.detail=[];
    $scope.formData.date_rencana=dateNow;
    $scope.formData.km_rencana=0;
    $scope.formData.cost_rencana=0;
    $scope.formData.is_internal=1;

    $scope.tipe_kegiatan=[
        {id:1,name:"Penggantian"},
        {id:2,name:"Perbaikan"},
        {id:3,name:"Pemeriksaan"}
    ];

    $scope.internal_eksternal=[
        {id:1,name:"Internal"},
        {id:0,name:"Eksternal"}
    ];

    $http.get(baseUrl+'/vehicle/maintenance/create/'+$stateParams.id).then(function(data) {
        $scope.data=data.data;
        $scope.formData.km_rencana=data.data.vehicle.last_km;
    });

    $scope.urut=0;

    $scope.openItem = function(id) {
        $scope.detail_id = id
    }

    $scope.$on("getItemWarehouse", function(e, v){
        var idx = $scope.formData.detail.findIndex(x => x.id == $scope.detail_id) 
        if(idx > -1) {
            $scope.formData.detail[idx].rack_code = v.rack_code
            $scope.formData.detail[idx].rack_id = v.rack_id
            $scope.formData.detail[idx].warehouse_receipt_detail_id = v.warehouse_receipt_detail_id
            $scope.formData.detail[idx].price = v.harga_beli
        }
        $scope.detail_id = null
    })

    $scope.$on("getItem", function(e, v){
        var idx = $scope.formData.detail.findIndex(x => x.id == $scope.detail_id) 
        if(idx > -1) {
            $scope.formData.detail[idx].item_id = v.id
            $scope.formData.detail[idx].price = v.harga_beli
        }
        $scope.detail_id = null
    })

    $scope.deleteRow=function(id) {
        $scope.formData.detail = $scope.formData.detail.filter(x => x.id != id)
    }

    $scope.addDetail = function() {
        var dt = {}
        dt.id = Math.round(Math.random() * 99999999)
        $scope.formData.detail.push(dt)
    }

    $scope.appendTable=function() {
        var html="";

        html+="<tr id='row-"+$scope.urut+"'>";
        html+="<td>"+$('#vmtype option:selected').text()+"</td>";
        html+="<td>"+$('#tktype option:selected').text()+"</td>";
        html+="<td>"+$('#item option:selected').text()+"</td>";
        html+="<td>"+$filter('number')($scope.detailData.qty)+"</td>";
        html+="<td>"+$filter('number')($scope.detailData.price)+"</td>";
        html+="<td><a ng-click='deleteRow("+$scope.urut+")'><span class='fa fa-trash'></span></a></td>";
        html+="</tr>";

        $scope.formData.detail.push({
            vehicle_maintenance_type_id:$scope.detailData.vehicle_maintenance_type_id,
            tipe_kegiatan:$scope.detailData.tipe_kegiatan,
            item_id:$scope.detailData.item.id,
            qty:$scope.detailData.qty,
            price:$scope.detailData.price,
        })

        $('#appendTable tbody').append($compile(html)($scope));
        $('#appendTable thead').append($compile(html)($scope));
        $scope.urut++;
        $scope.detailData={};
        $scope.detailData.qty=0;
        $scope.detailData.price=0;
    }

    

$scope.disBtn=false;
$scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
        type: "post",
        url: baseUrl+'/vehicle/maintenance/store_pengajuan/'+$stateParams.id+'?_token='+csrfToken,
        data: $scope.formData,
        success: function(data){
            $scope.$apply(function() {
                $scope.disBtn=false;
            });
            toastr.success("Data Berhasil Disimpan");
            $state.go('vehicle.vehicle.show.maintenance.pengajuan');
        },
error: function(xhr, response, status) {
    $scope.$apply(function() {
        $scope.disBtn=false;
    });
// console.log(xhr);
if (xhr.status==422) {
    var msgs="";
    $.each(xhr.responseJSON.errors, function(i, val) {
        msgs+=val+'<br>';
    });
    toastr.warning(msgs,"Validation Error!");
} else {
    toastr.error(xhr.responseJSON.message,"Error has Found!");
}
}
});
}

});
app.controller('vehicleVehicleShowMaintenanceEditRencana', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Info Kendaraan | Perawatan - Tambah";
    $scope.formData={};
    $scope.detailData={};
    $scope.formData.detail=[];
    $scope.formTitle="Input Rencana Perawatan";
    $scope.rencanaName="Rencana";
    $scope.formData.is_selesai=false;
    $scope.detailData.is_selesai=false;
    $scope.tipe_kegiatan=[
        {id:1,name:"Penggantian"},
        {id:2,name:"Perbaikan"},
        {id:3,name:"Pemeriksaan"},
    ];

    $scope.deleteRow=function(id) {
        $scope.formData.detail = $scope.formData.detail.filter(x => x.id != id)
    }
    
    $scope.editItem=function(id,qty,price) {
        $scope.itemDetail={};
        $scope.itemDetail.id=id;
        $scope.itemDetail.qty=qty;
        $scope.itemDetail.price=price;
        $scope.itemTitle="Input Rencana Pemakaian Barang";
        $('#editModal').modal('show');
    }

    $scope.show = function() {
        $http.get(baseUrl+'/vehicle/maintenance/edit_rencana/'+$stateParams.vm_id).then(function(data) {
            $scope.data=data.data;
            var dt=data.data.item;
            $scope.formData.detail=data.data.detail;
            $scope.formData.name=dt.name;
            $scope.formData.km_rencana=dt.km_rencana;
            $scope.formData.date_rencana=$filter('minDate')(dt.date_rencana);
            $scope.formData.cost_rencana=dt.cost_rencana;
            $scope.formData.vendor_id=dt.vendor_id;
            $scope.formData.description=dt.description;
            $scope.formData.is_internal=dt.is_internal;
        });
    }

    $scope.show()

    $scope.openItem = function(id) {
        $scope.detail_id = id
        console.log('openItem')
    }

    $scope.submitDetail=function() {
        $http.post(baseUrl+'/vehicle/maintenance/store_item_detail/'+$scope.itemDetail.id,$scope.itemDetail).then(function(data) {
            $('#editModal').modal('hide');
            oTable.ajax.reload();
        }, function(error) {
            if (error.status==422) {
                var det="";
                angular.forEach(error.data.errors,function(val,i) {
                    det+="- "+val+"<br>";
                });
                toastr.warning(det,error.data.message);
            } else {
                toastr.error(error.data.message,"Error Has Found !");
            }
        });
    }

    $scope.addDetail = function() {
        var dt = {}
        dt.id = Math.round(Math.random() * 99999999)
        $scope.formData.detail.push(dt)
    }

    $scope.$on("getItemWarehouse", function(e, v){
        var idx = $scope.formData.detail.findIndex(x => x.id == $scope.detail_id) 
        if(idx > -1) {
            $scope.formData.detail[idx].rack_code = v.rack_code
            $scope.formData.detail[idx].rack_id = v.rack_id
            $scope.formData.detail[idx].warehouse_receipt_detail_id = v.warehouse_receipt_detail_id
        }
        $scope.detail_id = null
    })

    $scope.appendTable=function() {
        $http.post(baseUrl+'/vehicle/maintenance/store_detail/'+$stateParams.vm_id,$scope.detailData).then(function(data) {
            oTable.ajax.reload();
            $scope.detailData={};
            $scope.detailData.qty=0;
            $scope.detailData.price=0;
        }, function(error) {
            toastr.error(error.data.message,"Error Has Found!");
        });
    }

    $scope.deletes=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/vehicle/maintenance/delete_detail/'+ids,{_token:csrfToken}).then(function success(data) {
                oTable.ajax.reload();
                toastr.success("Data Berhasil Dihapus!");
            }, function error(data) {
                toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
            });
        }
    }


    $scope.disBtn=false;
    $scope.submitForm=function() {
        $scope.disBtn=true;
        $.ajax({
            type: "post",
            url: baseUrl+'/vehicle/maintenance/store_rencana/'+$stateParams.vm_id+'?_token='+csrfToken,
            data: $scope.formData,
            success: function(data){
                $scope.$apply(function() {
                    $scope.disBtn=false;
                });
                toastr.success("Data Berhasil Disimpan");
                $state.go('vehicle.vehicle.show.maintenance.rencana');
                // oTable.ajax.reload();
            },
            error: function(xhr, response, status) {
                $scope.$apply(function() {
                    $scope.disBtn=false;
                });
                // console.log(xhr);
                if (xhr.status==422) {
                    var msgs="";
                    $.each(xhr.responseJSON.errors, function(i, val) {
                        msgs+=val+'<br>';
                    });
                    toastr.warning(msgs,"Validation Error!");
                } else {
                    toastr.error(xhr.responseJSON.message,"Error has Found!");
                }
            }
        });
    }

});
app.controller('vehicleVehicleShowMaintenanceShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Info Kendaraan | Perawatan - Detail";

    $scope.show = function() {
        $http.get(baseUrl+'/vehicle/maintenance/'+$stateParams.vm_id).then(function(data) {
            $scope.item=data.data.item;
            $scope.detail=data.data.detail;
            $scope.warehouse=data.data.warehouse;
        }, function(){
            $scope.show()
        });
    }
    $scope.show()

    $scope.gudangData={};

    $scope.go_maintenance=function() {
        if ($scope.item.is_internal==1) {
            $('#modalGudang').modal('show');
        } else {
            var conf=confirm("Apakah anda yakin ingin melanjutkan ke perawatan ?");
            if (conf) {
                $http.post(baseUrl+'/vehicle/maintenance/go_perawatan/'+$stateParams.vm_id).then(function(data) {
                    toastr.success("Kendaraan Dalam Perawatan","Berhasil!");
                    $state.go('vehicle.vehicle.show.maintenance.perawatan');
                }, function(error) {
                    toastr.error(error.data.message,"Error has Found!");
                });
            }
        }
    }

    $scope.prosesInternalPerawatan=function() {
        $scope.disBtn = true
        $http.post(baseUrl+'/vehicle/maintenance/go_perawatan/'+$stateParams.vm_id,$scope.gudangData).then(function(data) {
            $scope.disBtn = false
            toastr.success("Kendaraan Dalam Perawatan","Berhasil!");
            $('#modalGudang').modal('hide');
            $timeout(function() {
                $state.go('vehicle.vehicle.show.maintenance.perawatan');
            },1000);
        }, function(error) {
            $scope.disBtn=false;
            if (error.status==422) {
                var det="";
                angular.forEach(error.data.errors,function(val,i) {
                    det+="- "+val+"<br>";
                });
                toastr.warning(det,error.data.message);
            } else {
                toastr.error(error.data.message,"Error Has Found !");
            }
        });
    }
});
app.controller('vehicleVehicleShowMaintenancePengajuan', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Info Kendaraan | Perawatan - Pengajuan";

    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        order:[[3,'desc']],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/vehicle/maintenance_datatable',
            data: function(d) {
                d.vehicle_id=$stateParams.id,
                d.status=2;
            }
        },
        columns:[
        {data:"name",name:"name"},
        {data:"km_rencana",name:"km_rencana"},
        {data:"date_rencana",name:"date_rencana"},
        {data:"status_name",name:"maintenance_statuses.name"},
        {data:"total_rencana",name:"vehicle_maintenance_details.total_rencana"},
        {data:"date_realisasi",name:"date_realisasi"},
        {data:"total_realisasi",name:"vehicle_maintenance_details.total_realisasi"},
        {data:"km_realisasi",name:"km_realisasi"},
        {data:"vendor",name:"contacts.name"},
        {data:"action",name:"created_at",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });

    $scope.deletes=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/vehicle/maintenance/delete_maintenance',{params:{id:ids}}).then(function success(data) {
                oTable.ajax.reload();
                toastr.success("Data Berhasil Dihapus!");
            }, function error(data) {
                toastr.error("Tidak dapat menghapus data perawatan!","Error Has Found!");
            });
        }
    }

});
app.controller('vehicleVehicleShowMaintenanceRencana', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Info Kendaraan | Perawatan - Rencana";

    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        order:[[3,'desc']],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/vehicle/maintenance_datatable',
            data: function(d) {
                d.vehicle_id=$stateParams.id,
                d.status=3;
            }
        },
        columns:[
        {data:"name",name:"name"},
        {data:"km_rencana",name:"km_rencana"},
        {data:"date_rencana",name:"date_rencana"},
        {data:"status_name",name:"maintenance_statuses.name"},
        {
            data:"total_rencana",
            name:"vehicle_maintenance_details.total_rencana",
            className : "text-right"
        },
        {data:"date_realisasi",name:"date_realisasi"},
        {
            data:null, 
            className : 'text-right', 
            name:"vehicle_maintenance_details.total_realisasi",
            render : resp => $filter('number')(resp.total_realisasi)
        },
        {data:"km_realisasi", className : 'text-right', name:"km_realisasi"},
        {data:"vendor",name:"contacts.name"},
        {data:"action",name:"created_at",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });

    $scope.deletes=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/vehicle/maintenance/delete_maintenance',{params:{id:ids}}).then(function success(data) {
                oTable.ajax.reload();
                toastr.success("Data Berhasil Dihapus!");
            }, function error(data) {
                toastr.error("Tidak dapat menghapus data perawatan!","Error Has Found!");
            });
        }
    }


});
app.controller('vehicleVehicleShowMaintenancePerawatan', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Info Kendaraan | Perawatan - Perawatan";

    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        order:[[3,'desc']],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/vehicle/maintenance_datatable',
            data: function(d) {
                d.vehicle_id=$stateParams.id,
                d.status=4;
            }
        },
        columns:[
        {data:"name",name:"name"},
        {data:"km_rencana",name:"km_rencana"},
        {data:"date_rencana",name:"date_rencana"},
        {data:"status_name",name:"maintenance_statuses.name"},
        {
            data:"total_rencana",
            name:"vehicle_maintenance_details.total_rencana",
            className : 'text-right'
        },
        {data:"date_realisasi",name:"date_realisasi"},
        {data:"total_realisasi", className : 'text-right', name:"vehicle_maintenance_details.total_realisasi"},
        {data:"km_realisasi", className : 'text-right', name:"km_realisasi"},
        {data:"vendor",name:"contacts.name"},
        {data:"action",name:"created_at",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });
    $scope.deletes=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/vehicle/maintenance/delete_maintenance',{params:{id:ids}}).then(function success(data) {
                oTable.ajax.reload();
                toastr.success("Data Berhasil Dihapus!");
            }, function error(data) {
                toastr.error("Tidak dapat menghapus data perawatan!","Error Has Found!");
            });
        }
    }

});
app.controller('vehicleVehicleShowMaintenanceSelesai', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Info Kendaraan | Perawatan - Selesai";

    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        order:[[3,'desc']],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/vehicle/maintenance_datatable',
            data: function(d) {
                d.vehicle_id=$stateParams.id,
                d.status=5;
            }
        },
        columns:[
        {data:"name",name:"name"},
        {data:"km_rencana",name:"km_rencana"},
        {data:"date_rencana",name:"date_rencana"},
        {data:"status_name",name:"maintenance_statuses.name"},
        {
            data:"total_rencana",
            name:"vehicle_maintenance_details.total_rencana",
            className:"text-right"
        },
        {data:"date_realisasi",name:"date_realisasi"},
        {
            data:null, 
            className : 'text-right', 
            name:"vehicle_maintenance_details.total_realisasi",
            render : (resp) => $filter('number')(resp.total_realisasi) 
        },
        {data:"km_realisasi", className : 'text-right', name:"km_realisasi"},
        {data:"vendor",name:"contacts.name"},
        {data:"action",name:"created_at",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });
    $scope.deletes=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/vehicle/maintenance/delete_maintenance',{params:{id:ids}}).then(function success(data) {
                oTable.ajax.reload();
                toastr.success("Data Berhasil Dihapus!");
            }, function error(data) {
                toastr.error("Tidak dapat menghapus data perawatan!","Error Has Found!");
            });
        }
    }

});
app.controller('vehicleVehicleShowMaintenanceEditRealisasi', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Info Kendaraan | Perawatan - Input Realisasi";
    $scope.formData={};
    $scope.detailData={};
    $scope.formData.detail=[];
    $scope.formTitle="Input Realisasi Perawatan";
    $scope.rencanaName="Selesai";
    $scope.formData.is_selesai=true;
    $scope.detailData.is_selesai=true;
    $scope.tipe_kegiatan=[
        {id:1,name:"Penggantian"},
        {id:2,name:"Perbaikan"},
        {id:3,name:"Pemeriksaan"}
    ];

    $timeout(() => {
        $('#qty_realisasi_column').removeClass('ng-hide')
        $('#price_realisasi_column').removeClass('ng-hide')
    }, 1000)

    $scope.editItem=function(id,qty,price) {
        $scope.itemDetail={};
        $scope.itemDetail.id=id;
        $scope.itemDetail.qty=qty;
        $scope.itemDetail.price=price;
        $scope.itemTitle="Input Realisasi Pemakaian Barang";
        $('#editModal').modal('show');
    }

    $scope.is_realisasi = 1

    $scope.show = function() {
        $http.get(baseUrl+'/vehicle/maintenance/edit_rencana/'+$stateParams.vm_id).then(function(data) {
            $scope.data=data.data;
            var dt=data.data.item;
            $scope.formData.detail=data.data.detail;
            $scope.formData.name=dt.name;
            $scope.formData.km_rencana=dt.km_rencana;
            $scope.formData.date_rencana=$filter('minDate')(dt.date_rencana);
            $scope.formData.cost_rencana=dt.cost_rencana;
            $scope.formData.vendor_id=dt.vendor_id;
            $scope.formData.description=dt.description;
            $scope.formData.is_internal=dt.is_internal;
        }, function(){
            $scope.show()
        });
    }
    $scope.show()

    $scope.deleteRow=function(id) {
        $scope.formData.detail = $scope.formData.detail.filter(x => x.id != id)
    }

    $scope.submitDetail=function() {
        $http.post(baseUrl+'/vehicle/maintenance/store_item_detail/'+$scope.itemDetail.id,$scope.itemDetail).then(function(data) {
            $('#editModal').modal('hide');
            oTable.ajax.reload();
        }, function(error) {
            if (error.status==422) {
                var det="";
                angular.forEach(error.data.errors,function(val,i) {
                    det+="- "+val+"<br>";
                });
                toastr.warning(det,error.data.message);
            } else {
                toastr.error(error.data.message,"Error Has Found !");
            }
        });
    }

    $scope.appendTable=function() {
        $http.post(baseUrl+'/vehicle/maintenance/store_detail/'+$stateParams.vm_id,$scope.detailData).then(function(data) {
            oTable.ajax.reload();
            $scope.detailData={};
            $scope.detailData.qty=0;
            $scope.detailData.price=0;
        }, function(error) {
            toastr.error(error.data.message,"Error Has Found!");
        });
    }
    
    $scope.addDetail = function() {
        var dt = {}
        dt.id = Math.round(Math.random() * 99999999)
        $scope.formData.detail.push(dt)
        setTimeout(function () {
            $('#qty_realisasi_column').removeClass('ng-hide')
            $('#price_realisasi_column').removeClass('ng-hide')
        }, 400)
    }

    $scope.deletes=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/vehicle/maintenance/delete_detail/'+ids,{_token:csrfToken}).then(function success(data) {
                oTable.ajax.reload();
                toastr.success("Data Berhasil Dihapus!");
            }, function error(data) {
                toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
            });
        }
    }


    $scope.disBtn=false;
    $scope.submitForm=function() {
        $scope.disBtn=true;
        $.ajax({
            type: "post",
            url: baseUrl+'/vehicle/maintenance/store_selesai/'+$stateParams.vm_id+'?_token='+csrfToken,
            data: $scope.formData,
            success: function(data){
                $scope.$apply(function() {
                    $scope.disBtn=false;
                });
                toastr.success("Data Berhasil Disimpan");
                $state.go('vehicle.vehicle.show.maintenance.selesai');
// oTable.ajax.reload();
},
error: function(xhr, response, status) {
    $scope.$apply(function() {
        $scope.disBtn=false;
    });
// console.log(xhr);
if (xhr.status==422) {
    var msgs="";
    $.each(xhr.responseJSON.errors, function(i, val) {
        msgs+=val+'<br>';
    });
    toastr.warning(msgs,"Validation Error!");
} else {
    toastr.error(xhr.responseJSON.message,"Error has Found!");
}
}
});
    }

});
app.controller('vehicleVehicleShowDetailDetail', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Info Kendaraan | Detail";

    $http.get(baseUrl+'/vehicle/vehicle/'+$stateParams.id).then(function(data) {
        $scope.item=data.data.item;
        $scope.item.active_name = $scope.item.is_active ? "Aktif" : "Tidak Aktif";
    });
});
app.controller('vehicleVehicleShowDetailDriver', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Info Kendaraan | Detail";
    $scope.formData={};
    $scope.disBtn=false;
    $scope.addDriver=function() {
        $scope.formData={};
        $('#modal').modal('show');
    }

    $scope.status=[
    {id:1,name:"Driver Utama"},
    {id:2,name:"Driver Cadangan"},
    {id:3,name:"Helper"},
    {id:4,name:"Driver Vendor"},
    ];

    $http.get(baseUrl+'/vehicle/vehicle/driver/'+$stateParams.id).then(function(data) {
        $scope.data=data.data;
        $scope.data.active_driver = $scope.data.driver;
    });

    $scope.change_status = function() {
        $scope.data.active_driver = $scope.data.driver.filter(function(item){
            return item.driver_status == $scope.formData.status;
        });
    }

    $scope.submitForm=function() {
        $scope.disBtn=true;
        $http.post(baseUrl+'/vehicle/vehicle/store_driver/'+$stateParams.id,$scope.formData).then(function(data) {
            $('#modal').modal('hide');
            $timeout(function() {
                $state.reload();
            },1000);
            toastr.success("Data Berhasil Disimpan!");
            $scope.disBtn=true;
        }, function(error) {
            $scope.disBtn=true;
            if (error.status==422) {
                var det="";
                angular.forEach(error.data.errors,function(val,i) {
                    det+="- "+val+"<br>";
                });
                toastr.warning(det,error.data.message);
            } else {
                toastr.error(error.data.message,"Error Has Found !");
            }
        });
    }

    $scope.deletes=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/driver/driver/delete_vehicle/'+ids,{_token:csrfToken}).then(function success(data) {
                $state.reload();
                toastr.success("Data Berhasil Dihapus!");
            }, function error(data) {
                toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
            });
        }
    }

});
app.controller('vehicleVehicleShowDetailBody', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Info Kendaraan | Detail";

    $http.get(baseUrl+'/vehicle/vehicle/body/'+$stateParams.id).then(function(data) {
        $scope.item=data.data.item;
        $scope.detail_body=data.data.detail_body;
    });
});
app.controller('vehicleVehicleShowDetailChecklist', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Info Kendaraan | Detail";

    $http.get(baseUrl+'/vehicle/vehicle/body/'+$stateParams.id).then(function(data) {
        $scope.item=data.data.item;
        $scope.detail_checklist=data.data.detail_checklist;
    });
});
app.controller('vehicleVehicleShowDetailInsurance', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Info Kendaraan | Detail";
    $scope.formData={};
    $scope.isEdit = false;
    $scope.creates=function() {
        $scope.modalTitle="Tambah Asuransi";
        $scope.formData={};
        $scope.formData.is_active=1;
        $scope.formData.type=1;
        $scope.formData.payment=1;
        $scope.isEdit = false;
        $('#modal').modal('show');
    }
    $scope.type=[
    {id:1,name:"TLO"},
    {id:2,name:"All Risk"},
    ];
    $scope.payment=[
    {id:1,name:"Cash"},
    {id:2,name:"Kredit"},
    ];

    $http.get(baseUrl+'/vehicle/vehicle/insurance/'+$stateParams.id).then(function(data) {
        $scope.data=data.data;
    });

    $scope.edits = function(ids) {
        $http.get(baseUrl+'/vehicle/vehicle/insurance_detail/' + ids).then(function(data) {
            $scope.formData = data.data.item;
            $scope.formData.start_date = $filter('minDate')(data.data.item.start_date);
            $scope.formData.end_date = $filter('minDate')(data.data.item.end_date);
            $scope.isEdit = true;
            $('#modal').modal('show');
        });
    }

    $scope.deletes=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/vehicle/vehicle/insurance_detail/'+ids,{_token:csrfToken}).then(function success(data) {
                $state.reload();
                toastr.success("Data Berhasil Dihapus!");
            }, function error(data) {
                toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
            });
        }
    }

    $scope.disBtn=false;
    $scope.submitForm=function() {
        $scope.disBtn=true;
        $http.post(baseUrl+'/vehicle/vehicle/store_insurance/'+$stateParams.id,$scope.formData).then(function(data) {
            $('#modal').modal('hide');
            $timeout(function() {
                $state.reload();
            },1000);
            toastr.success("Data Berhasil Disimpan!");
            $scope.disBtn=false;
        }, function(error) {
            $scope.disBtn=false;
            if (error.status==422) {
                var det="";
                angular.forEach(error.data.errors,function(val,i) {
                    det+="- "+val+"<br>";
                });
                toastr.warning(det,error.data.message);
            } else {
                toastr.error(error.data.message,"Error Has Found !");
            }
        });
    }

    $scope.disBtn=false;
    $scope.updateForm = function() {
        $scope.disBtn=true;
        $http.put(baseUrl+'/vehicle/vehicle/insurance_detail/'+$scope.formData.id, $scope.formData).then(function(data) {
            $('#modal').modal('hide');
            $timeout(function() {
                $state.reload();
            },1000);
            $scope.isEdit = false;
            toastr.success("Data Berhasil Disimpan!");
            $scope.disBtn=false;
        }, function(error) {
            $scope.disBtn=false;
            if (error.status==422) {
                var det="";
                angular.forEach(error.data.errors,function(val,i) {
                    det+="- "+val+"<br>";
                });
                toastr.warning(det,error.data.message);
            } else {
                toastr.error(error.data.message,"Error Has Found !");
            }
        });
    }

});
app.controller('vehicleVehicleShowDetailDocument', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Info Kendaraan | Detail";
    $scope.formData={};
    $scope.creates=function() {
        $scope.modalTitle="Tambah Berkas";
        $scope.formData={};
        $scope.formData.is_active=1;
        $scope.formData.type=1;
        $scope.formData.payment=1;
        $('#modal').modal('show');
    }
    $scope.type=[
    {id:1,name:"STNK"},
    {id:2,name:"SIUP"},
    {id:3,name:"KEUR"},
    {id:4,name:"KIM/IMK"},
    {id:5,name:"PERBAIKAN"},
    {id:6,name:"BPKB"},
    {id:7,name:"FOTO KENDARAAN"},
    {id:8,name:"LAIN - LAIN"},
    ];
    $scope.urls=baseUrl;
    $http.get(baseUrl+'/vehicle/vehicle/document/'+$stateParams.id).then(function(data) {
        $scope.data=data.data;
    });

    $scope.deleteFile=function(id) {
        var cofs=confirm("Apakah anda yakin ?");
        if (!cofs) {
            return null;
        }
        $http.post(baseUrl+'/vehicle/vehicle/delete_document',{id:id}).then(function(data){
            toastr.success("Berhasil","File Berhasil Dihapus!","success");
            $state.reload()
        })
    }

    $scope.disBtn=false;
    $scope.submitForm=function() {
        $scope.disBtn=true;
        $.ajax({
            type: "post",
            url: baseUrl+'/vehicle/vehicle/store_document/'+$stateParams.id+'?_token='+csrfToken,
            contentType: false,
            cache: false,
            processData: false,
            data: new FormData($('#forms')[0]),
            success: function(data){
                $scope.$apply(function() {
                    $scope.disBtn=false;
                });
                $('#modal').modal('hide');
                toastr.success("Data Berhasil Disimpan");
// $state.go('marketing.inquery.show.document',{id:$stateParams.id});
$timeout(function() {
    $state.reload();
},1000)
},
error: function(xhr, response, status) {
    $scope.$apply(function() {
        $scope.disBtn=false;
    });
// console.log(xhr);
if (xhr.status==422) {
    var msgs="";
    $.each(xhr.responseJSON.errors, function(i, val) {
        msgs+=val+'<br>';
    });
    toastr.warning(msgs,"Validation Error!");
} else {
    toastr.error(xhr.responseJSON.message,"Error has Found!");
}
}
});

    }

});
app.controller('vehicleVehicleShowDetailRate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Info Kendaraan | Detail";
    $scope.formData={};
    $scope.filterData={};
    $scope.creates=function() {
        $scope.formData={};
        $('#modal').modal('show');
    }

    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        ordering:false,
        paging:false,
        searching:false,
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/vehicle/target_rate_datatable',
            data: function(d) {
                d.year=$scope.filterData.year;
            }
        },
        columns:[
        {data:"months",name:"months"},
        {data:"plan",name:"plan",className:"text-right"},
        {data:"realisasi",name:"realisasi",className:"text-right"},
        {data:"up_at",name:"up_at"},
        {data:"action",name:"created_at", sorting:false},
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });

    $scope.changeYear=function() {
        oTable.ajax.reload();
    }
    $scope.detailData={};
    $scope.edits=function(id,plan) {
        $scope.detailData.id=id;
        $scope.detailData.plan=plan;
        $('#modalEdit').modal('show');
    }

    $scope.disBtn=false;
    $scope.submitDetail=function() {
        $scope.disBtn=true;
        $http.post(baseUrl+'/vehicle/vehicle/store_detail_rate',$scope.detailData).then(function(data) {
            $('#modalEdit').modal('hide');
            oTable.ajax.reload();
            toastr.success("Data Berhasil Disimpan!");
            $scope.disBtn=false;
        }, function(error) {
            $scope.disBtn=false;
            if (error.status==422) {
                var det="";
                angular.forEach(error.data.errors,function(val,i) {
                    det+="- "+val+"<br>";
                });
                toastr.warning(det,error.data.message);
            } else {
                toastr.error(error.data.message,"Error Has Found !");
            }
        });
    }


    $http.get(baseUrl+'/vehicle/vehicle/rate/'+$stateParams.id).then(function(data) {
        $scope.years=[];
        angular.forEach(data.data.years, function(val,i) {
            $scope.years.push(
                {id:val.tahun,name:val.tahun}
                )
        });
    });

    $scope.disBtn=false;
    $scope.submitForm=function() {
        $scope.disBtn=true;
        $http.post(baseUrl+'/vehicle/vehicle/store_rate/'+$stateParams.id,$scope.formData).then(function(data) {
            $('#modal').modal('hide');
            oTable.ajax.reload();
            toastr.success("Data Berhasil Disimpan!");
            $scope.disBtn=false;
        }, function(error) {
            $scope.disBtn=false;
            if (error.status==422) {
                var det="";
                angular.forEach(error.data.errors,function(val,i) {
                    det+="- "+val+"<br>";
                });
                toastr.warning(det,error.data.message);
            } else {
                toastr.error(error.data.message,"Error Has Found !");
            }
        });
    }

});
