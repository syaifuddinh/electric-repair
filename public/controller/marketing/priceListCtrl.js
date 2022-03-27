app.controller('marketing', function($scope, $http, $rootScope) {
    $rootScope.pageTitle='Marketing';
});

app.controller('marketingPriceList', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
    $rootScope.pageTitle="Price List";
    $('.ibox-content').addClass('sk-loading');
    $scope.formData = {}
    $scope.isFilter = false;

    $scope.showData = function() {
        $http.get(baseUrl+'/marketing/price_list/create').then(function(data) {
            $scope.data=data.data;
        });
    }
    $scope.showData()

    $scope.reset_filter = function() {
        $scope.formData = {};
        $scope.refresh_table();
    }

    $scope.refresh_table=function() {
        oTable.ajax.reload();
    }

    // $scope.export_excel = function() {
    //     var paramsObj = oTable.ajax.params();
    //     var params = $.param(paramsObj);
    //     var url = baseUrl + '/excel/price_list_export?';
    //     url += params;
    //     location.href = url; 
    // }

    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        dom: 'Blfrtip',
        buttons: [{
            extend: 'excel',
            enabled: true,
            action: newExportAction,
            text: '<span class="fa fa-file-excel-o"></span> Export Excel',
            className: 'btn btn-default btn-sm pull-right m-l-sm',
            filename: 'Logistic - Price List - ' + new Date,
            sheetName: 'Data',
            title: 'Logistic - Price List',
            exportOptions: {
            rows: {
                selected: true
            }
            },
        }],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/marketing/price_list_datatable',
            data : function(d) {
                d.service_type_id = $scope.formData.service_type_id;
                d.company_id = $scope.formData.company_id;
            },
            dataSrc: function(d) {
                $('.ibox-content').removeClass('sk-loading');
                return d.data;
            }
        },
        columns:[
        {data:"company.name",name:"company.name"},
        {data:"code",name:"code"},
        {
            data:null,
            name:"route.name",
            render : function(resp) {
                if(resp['route'] != null) {
                    return resp['route']['name'];
                }
                else {
                    return '';
                }
            }
        },
        {data:"name",name:"name"},
        {data:"commodity.name",name:"commodity.name"},
        {
            data:null,
            name:"piece.name",
            render : function(resp) {
                if(resp['piece'] != null) {
                    return resp['piece']['name'];
                }
                else {
                    return '';
                }
            }
        },
        {data:"service.name"},
        {data:"service.service_type.name"},
        {
            data:null,
            name:"moda.name",
            render : function(resp) {
                if(resp['moda'] != null) {
                    return resp['moda']['name'];
                }
                else {
                    return '';
                }
            }
        },
        {
            data:null,
            name:"vehicle_type.name",
            render : function(resp) {
                if(resp['vehicle_type'] != null) {
                    return resp['vehicle_type']['name'];
                }
                else {
                    return '';
                }
            }
        },
        {
            data:null,
            searchable:false,
            orderable:false,
            className:'text-right',
            render:function(resp) {
                outp = ''
                if(resp.service_type_id == 1 || ((resp.service_type_id == 12 || resp.service_type_id == 13) && resp.handling_type == 1)) {
                    outp += $filter('number')(resp.price_tonase) + ' (kg)<br>'
                    outp += $filter('number')(resp.price_volume) + ' (m<sup>3</sup>)<br>'
                    outp += $filter('number')(resp.price_item) + ' (Per Item)<br>'
                    outp += $filter('number')(resp.price_borongan) + ' (Borongan)<br>'
                } else {
                    outp += $filter('number')(resp.price_full) + '<br>'                    
                }

                return outp
            }
        },
        {data:"action",name:"action",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            if($rootScope.roleList.includes('marketing.price.price_list.detail')) {
                $(row).find('td').attr('ui-sref', 'marketing.price_list.show({id:' + data.id + '})')
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
            $http.delete(baseUrl+'/marketing/price_list/'+ids,{_token:csrfToken}).then(function success(data) {
// $state.reload();
oTable.ajax.reload();
toastr.success("Data Berhasil Dihapus!");
}, function error(data) {
    toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
});
        }
    }

});
app.controller('marketingPriceListCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Add Price List";
    $scope.isedit=false;
    $('.ibox-content').addClass('sk-loading');
    $scope.detailData = {}
    $scope.formData = { detail: [], minimum_detail: [] }

    $scope.sizes = [
    {
        'value' : 20,
        'unit' : 'STD'
    },
    {
        'value' : 40,
        'unit' : 'STD/HC'
    },
    {
        'value' : 40,
        'unit' : 'RF'
    },
    ];


    $scope.showHandlingType = function() {
            $http.get(baseUrl+'/setting/setting/handling_type').then(function(data){
                $scope.handling_type = data.data[0].content.settings
            }, function() {
                $timeout(function(){
                    $scope.showHandlingType()
                }, 10000)
            })
    }
    $scope.showHandlingType()

    $scope.switchContainer = function() {
        $scope.container_types = $scope.data.container_type.filter(value => value.size == $scope.formData.size.value && value.unit == $scope.formData.size.unit )
    }

    $scope.countTotal = function() {
        var prices = $scope.formData.detail.map(value => parseInt(value.price))
        prices = prices.filter(value => value || false)
        $scope.grandtotal = prices.reduce(
            (x, y) => x + y
        )

    }

    $scope.appendTableService=function() {
    
        $scope.formData.detail.push({
          service_id:$scope.detailData.service.id,
        })
        var index = $scope.formData.detail.findIndex(value => value.service_id == $scope.detailData.service.id);
        var html = '';
        html+="<tr>"
        html+="<td>"+$scope.detailData.service.name+"</td>"
        html+="<td>"+$scope.detailData.service.service_type.name+"</td>"
        html+="</tr>"

        $('#appendTable tbody').append($compile(html)($scope));
        $scope.detailData = {};
    }

    $scope.getServicePacket = function() {
        if($scope.formData.is_warehouse == 0) {
            $scope.formData.stype = null
        }
        $('#appendTable tbody').html('');
        $scope.formData.detail = []
        $scope.grandtotal = 0
        var service = $scope.data.service.find(x => x.id == $scope.formData.service_id)
        if(service == null) {
            service = $scope.data.service_warehouse.find(x => x.id == $scope.formData.service_id)
        }
        $scope.service = service;
        if(service.is_packet == 1) {
            $http.get(baseUrl+'/marketing/combined_price/service/'+$scope.formData.service_id).then(function(data) {
                $scope.formData.detail=[];

                angular.forEach(data.data.detail, function(value){
                    $scope.detailData.service = value.service;
                    $scope.appendTableService();
                })
            });
        }
    }

    $scope.create = function() {
        $http.get(baseUrl+'/marketing/price_list/create').then(function(data) {
            $scope.data=data.data;
            $scope.container_types = data.data.container_type;
            $scope.type_1=data.data.type_1;
            $scope.type_2=data.data.type_2;
            $scope.type_3=data.data.type_3;
            $scope.type_4=data.data.type_4;
            $scope.type_5=data.data.type_5;
            $scope.type_6=data.data.type_6;
            $scope.type_7=data.data.type_7;
            $scope.type_10=data.data.type_10;
            $('.ibox-content').removeClass('sk-loading');
        }, function() {
            $scope.create()
        });
    }
    $scope.create()

    $scope.div_tarif=false;
    $scope.dom_switch=function(ids,company,isedit) {
        if(!$scope.data) {
            $scope.create()
            $timeout(function() {
                $scope.dom_switch(ids,company,isedit)
            }, 800)
        }
        var service = $scope.data.service.find(x => x.id == $scope.formData.service_id)
        if(!service) {
            return
        }
        $scope.service = service
        if (service.service_type_id == 1) {
            $scope.formData.stype_id=1;
            $scope.formData.min_type=1;
            $scope.formData.price_tonase=0;
            $scope.formData.min_tonase=0;
            $scope.formData.price_volume=0;
            $scope.formData.min_volume=0;
            $scope.formData.price_item=0;
            $scope.formData.min_item=0;
            $scope.div_trayek=true;
            $scope.div_komoditas=false;
            $scope.div_satuan=false;
            $scope.div_moda=true;
            $scope.div_armada=false;
            $scope.div_container=false;
            $scope.div_tarif_min=true;
            $scope.div_tarif=false;
            $scope.div_rack=false;
            $scope.div_storage_tonase=false;
            $scope.div_storage_volume=false;
            $scope.div_handling_tonase=false;
            $scope.div_handling_volume=false;
            $scope.div_warehouse=false;
        } else if (service.service_type_id == 2) {
            $scope.formData.stype_id=2;
            $scope.div_trayek=true;
            $scope.div_komoditas=false;
            $scope.div_satuan=false;
            $scope.div_moda=false;
            $scope.div_armada=false;
            $scope.div_container=true;
            $scope.div_tarif_min=false;
            $scope.div_tarif=true;
            $scope.div_rack=false;
            $scope.div_storage_tonase=false;
            $scope.div_storage_volume=false;
            $scope.div_handling_tonase=false;
            $scope.div_handling_volume=false;
            $scope.div_warehouse=false;
        } else if (service.service_type_id == 3) {
            $scope.formData.stype_id=3;
            $scope.div_trayek=true;
            $scope.div_komoditas=false;
            $scope.div_satuan=false;
            $scope.div_moda=false;
            $scope.div_armada=true;
            $scope.div_container=false;
            $scope.div_tarif_min=false;
            $scope.div_tarif=true;
            $scope.div_rack=false;
            $scope.div_storage_tonase=false;
            $scope.div_storage_volume=false;
            $scope.div_handling_tonase=false;
            $scope.div_handling_volume=false;
            $scope.div_warehouse=false;
        } else if (service.service_type_id == 4) {
            $scope.formData.stype_id=4;
            $scope.div_trayek=true;
            $scope.div_komoditas=false;
            $scope.div_satuan=true;
            $scope.div_moda=false;
            $scope.div_armada=true;
            $scope.div_container=false;
            $scope.div_tarif_min=false;
            $scope.div_tarif=true;
            $scope.div_rack=false;
            $scope.div_storage_tonase=false;
            $scope.div_storage_volume=false;
            $scope.div_handling_tonase=false;
            $scope.div_handling_volume=false;
            $scope.div_warehouse=false;
        } else if (service.service_type_id == 5) {
            $scope.formData.stype_id=5;
            $scope.div_trayek=false;
            $scope.div_komoditas=true;
            $scope.div_satuan=false;
            $scope.div_moda=false;
            $scope.div_armada=false;
            $scope.div_container=false;
            $scope.div_tarif_min=false;
            $scope.div_tarif=false;
            $scope.div_rack=false;
            $scope.div_storage_tonase=true;
            $scope.div_storage_volume=true;
            $scope.div_handling_tonase=false;
            $scope.div_handling_volume=false;
            $scope.div_warehouse=true;
        } else if (service.service_type_id == 6) {
            $scope.formData.stype_id=6;
            $scope.div_trayek=false;
            $scope.div_komoditas=false;
            $scope.div_satuan=true;
            $scope.div_moda=false;
            $scope.div_armada=false;
            $scope.div_container=false;
            $scope.div_tarif_min=false;
            $scope.div_tarif=true;
            $scope.div_rack=false;
            $scope.div_storage_tonase=false;
            $scope.div_storage_volume=false;
            $scope.div_handling_tonase=false;
            $scope.div_handling_volume=false;
            $scope.div_warehouse=false;
        } else if (service.service_type_id == 7){
            $scope.formData.stype_id=7;
            $scope.div_trayek=false;
            $scope.div_komoditas=false;
            $scope.div_satuan=true;
            $scope.div_moda=false;
            $scope.div_armada=false;
            $scope.div_container=false;
            $scope.div_tarif_min=false;
            $scope.div_tarif=true;
            $scope.div_rack=false;
            $scope.div_storage_tonase=false;
            $scope.div_storage_volume=false;
            $scope.div_handling_tonase=false;
            $scope.div_handling_volume=false;
            $scope.div_warehouse=false;
        } else {
            $scope.formData.stype_id=$scope.service.service_type_id;
            $scope.div_trayek=false;
            $scope.div_komoditas=false;
            $scope.div_satuan=false;
            $scope.div_moda=false;
            $scope.div_armada=false;
            $scope.div_container=false;
            $scope.div_tarif_min=false;
            $scope.div_tarif=false;
            $scope.div_rack=false;
            $scope.div_storage_tonase=false;
            $scope.div_storage_volume=false;
            $scope.div_handling_tonase=false;
            $scope.div_handling_volume=false;
            $scope.div_warehouse=false;
        }

    }

    $scope.$watch('formData.service_id', function(){
        $scope.dom_switch($scope.formData.service_id, $scope.formData.company_id, $scope.isedit)
    }) 

    $scope.disBtn=false;
    $scope.submitForm=function() {
        $scope.disBtn=true;
        $.ajax({
            type: "post",
            url: baseUrl+'/marketing/price_list?_token='+csrfToken,
            data: $scope.formData,
            success: function(data){
                $scope.$apply(function() {
                    $scope.disBtn=false;
                });
                toastr.success("Data Berhasil Disimpan");
                $state.go('marketing.price_list');
            },
            error: function(xhr, response, status) {
                $scope.$apply(function() {
                    $scope.disBtn=false;
                });
                // console.log(xhr);
                if (xhr.status==422) {
                    var msgs="";
                    $.each(xhr.responseJSON.errors, function(i, val) {
                        msgs+='- '+val+'<br>';
                    });
                    toastr.warning(msgs,"Validation Error!");
                } else {
                    toastr.error(xhr.responseJSON.message,"Error has Found!");
                }
            }
        });
    }

    $scope.minTypeChange = function() {
        $scope.formData.price_tonase = 0;
        $scope.formData.min_tonase = 0;
        $scope.formData.price_volume = 0;
        $scope.formData.min_volume = 0;
        $scope.formData.price_item = 0;
        $scope.formData.min_item = 0;
        $scope.formData.minimal_detail = [];
    }

    $scope.InsertOrUpdate = 0;
    $scope.indexEdit = null;

    $scope.addMinMultipleDetail = function () {
        $scope.indexEdit = null;
        $scope.price_tonase = 0;
        $scope.min_tonase = 0;
        $scope.price_volume = 0;
        $scope.min_volume = 0;
        $scope.price_item = 0;
        $scope.min_item = 0;
        $scope.InsertOrUpdate = 0;
        $('#modalMinMultipleDetail').modal('show');
    }

    $scope.submitFormMinMultipleDetail = function () {
        var preData = {
            price_per_kg: $scope.price_tonase,
            min_kg: $scope.min_tonase,
            price_per_m3: $scope.price_volume,
            min_m3: $scope.min_volume,
            price_per_item: $scope.price_item,
            min_item: $scope.min_item
        };
        if($scope.InsertOrUpdate == 0) {
            $scope.formData.minimal_detail.push(preData);
        }
        else {
            $scope.formData.minimal_detail[$scope.indexEdit] = preData;
        }

        $scope.indexEdit = null;
        $scope.price_tonase = 0;
        $scope.min_tonase = 0;
        $scope.price_volume = 0;
        $scope.min_volume = 0;
        $scope.price_item = 0;
        $scope.min_item = 0;
        $scope.InsertOrUpdate = 0;
        $('#modalMinMultipleDetail').modal('hide');
    }

    $scope.deleteMinMultipleDetail = function (index) {
        for( var i = 0; i < $scope.formData.minimal_detail.length; i++) { 
            if ( index === i) { 
                $scope.formData.minimal_detail.splice(i, 1); 
            }
        }
    }

    $scope.editMinMultipleDetail = function (index) {
        $scope.indexEdit = index;
        $scope.price_tonase = $scope.formData.minimal_detail[index].price_per_kg;
        $scope.min_tonase = $scope.formData.minimal_detail[index].min_kg;
        $scope.price_volume = $scope.formData.minimal_detail[index].price_per_m3;
        $scope.min_volume = $scope.formData.minimal_detail[index].min_m3;
        $scope.price_item = $scope.formData.minimal_detail[index].price_per_item;
        $scope.min_item = $scope.formData.minimal_detail[index].min_item;
        $scope.InsertOrUpdate = 1;
        $('#modalMinMultipleDetail').modal('show');
    }

});

app.controller('marketingPriceListEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Edit Price List";
    $scope.isedit=false;
    $('.ibox-content').addClass('sk-loading');
    var labelWarehouse = '';

    $scope.showHandlingType = function() {
            $http.get(baseUrl+'/setting/setting/handling_type').then(function(data){
                $scope.handling_type = data.data[0].content.settings
            }, function() {
                $timeout(function(){
                    $scope.showHandlingType()
                }, 10000)
            })
    }
    $scope.showHandlingType()

    $scope.countTotal = function() {
        var prices = $scope.formData.detail.map(value => parseInt(value.price))
        prices = prices.filter(value => value || false)
        $scope.grandtotal = prices.reduce(
            (x, y) => x + y
        )
        $scope.formData.price_full = $scope.grandtotal
    }

    $scope.insertRoute = function() {
          $rootScope.insertBuffer()
          $state.go('setting.route.create')
    }

    $scope.appendTableService=function() {
    
        $scope.formData.detail.push({
          service_id:$scope.detailData.service.id,
        })
        var index = $scope.formData.detail.findIndex(value => value.service_id == $scope.detailData.service.id);
        var html = '';
        html+="<tr>"
        html+="<td>"+$scope.detailData.service.name+"</td>"
        html+="<td>"+$scope.detailData.service.service_type.name+"</td>"
        html+="</tr>"

        $('#appendTable tbody').append($compile(html)($scope));
        $scope.detailData = {};
    }

    $scope.getServicePacket = function() {
        $('#appendTable tbody').html('');
        $scope.formData.detail = []
        $scope.grandtotal = 0
        var service = $scope.data.service.find(x => x.id == $scope.formData.service_id)
        $scope.service = service
        if(service == null) {
            service = $scope.data.service_warehouse.find(x => x.id == $scope.formData.service_id)
        }
        if(service.is_packet == 1) {
            $http.get(baseUrl+'/marketing/combined_price/service/'+$scope.formData.service_id).then(function(data) {
                $scope.formData.detail=[];

                angular.forEach(data.data.detail, function(value){
                    $scope.detailData.service = value.service;
                    $scope.appendTableService();
                })
            });
        }
    }

    $scope.sizes = [
    {
        'value' : 20,
        'unit' : 'STD'
    },
    {
        'value' : 40,
        'unit' : 'STD/HC'
    },
    {
        'value' : 40,
        'unit' : 'RF'
    },
    ];

    $scope.switchContainer = function() {
        $scope.container_types = $scope.data.container_type.filter(value => value.size == $scope.formData.size.value && value.unit == $scope.formData.size.unit )
    }

    $scope.domSwitchWarehouse = function(dom){
    // var el = $(dom);
    var warehouse = $rootScope.findJsonId($scope.formData.service_id, $scope.data.service_warehouse);
    var labelWarehouse = '';
    if(warehouse) {
        labelWarehouse = warehouse.name;
    }
    $scope.labelWarehouse = labelWarehouse.toLowerCase();

}


$scope.show = function() {

    $http.get(baseUrl+'/marketing/price_list/'+$stateParams.id+'/edit').then(function(data) {
        $('.ibox-content').removeClass('sk-loading');
        $scope.data=data.data;
        $scope.container_types = data.data.container_type;
        
        var dt=data.data.item;
        $scope.type_1=data.data.type_1;
        $scope.type_2=data.data.type_2;
        $scope.type_3=data.data.type_3;
        $scope.type_4=data.data.type_4;
        $scope.type_5=data.data.type_5;
        $scope.type_6=data.data.type_6;
        $scope.type_7=data.data.type_7;
        $scope.type_10=data.data.type_10;

        $scope.formData={
            handling_type:dt.handling_type,
            company_id:dt.company_id,
            price_type:dt.price_type,
            combined_price_id:dt.combined_price_id,
            min_tonase:dt.min_tonase,
            price_tonase:dt.price_tonase,
            min_volume:dt.min_volume,
            price_volume:dt.price_volume,
            min_item:dt.min_item,
            price_item:dt.price_item,
            min_borongan:dt.min_borongan,
            price_borongan:dt.price_borongan,
            price_full:dt.price_full,
            route_id:dt.route_id,
            code:dt.code,
            commodity_id:dt.commodity_id,
            name:dt.name,
            piece_id:dt.piece_id,
            is_warehouse:dt.is_warehouse,
            service_id:dt.service_id,
            moda_id:dt.moda_id,
            vehicle_type_id:dt.vehicle_type_id,
            description:dt.description,
            price_handling_tonase:dt.price_handling_tonase,
            price_handling_volume:dt.price_handling_volume,
            rack_id:dt.rack_id,
            container_type_id:dt.container_type_id,
            stype_id:dt.service_type_id,
            warehouse_id:dt.warehouse_id,
            free_storage_day:dt.free_storage_day,
            over_storage_price:dt.over_storage_price,
            min_type: dt.min_type ?? 1,
            detail:[],
            minimal_detail:[]
        }

        if (dt.service_type_id==1) {
          if (dt.vehicle_type_id) {
            $scope.formData.ltl_lcl=1
          } else {
            $scope.formData.ltl_lcl=2
          }
        }
        $scope.getServicePacket()
        if($scope.formData.container_type_id) {
            var container = data.data.container_type.find(value => value.id == $scope.formData.container_type_id)
            $scope.formData.size = $scope.sizes.find(size => size.value == container.size && size.unit == container.unit)
            $scope.switchContainer();
        }
        $scope.dom_switch(dt.service_id,dt.company_id,true);
        $scope.detailData = {}
        if(dt.combined_price_id) {
            $scope.formData.stype_id = null
            angular.forEach(dt.detail, function(value, index){
                $scope.detailData.service = value.service
                $scope.appendTableService()
                $scope.formData.detail[index].price = value.price
            })
        }

        if($scope.data.item.service_type_id == 1) {
            if($scope.data.item.min_type == 2) {
                $scope.formData.minimal_detail = $scope.data.price_list_minimum_detail;
            }
        }
    }, function() {
        $scope.show()
    });
}
$scope.show()

$scope.div_tarif=false;
$scope.dom_switch=function(ids,company,isedit) {
// console.log(ids);
$scope.formData.code=$scope.data.item.code;
$scope.formData.name=$scope.data.item.name;

var service = $scope.data.service.find(x => x.id == $scope.formData.service_id)
$scope.service = service
$scope.div_tarif=false;
if (service.service_type_id == 1) {
    $scope.formData.stype_id=1;
    if (isedit==false) {
        $scope.formData.min_type = 1;
        $scope.formData.price_tonase=0;
        $scope.formData.min_tonase=0;
        $scope.formData.price_volume=0;
        $scope.formData.min_volume=0;
        $scope.formData.price_item=0;
        $scope.formData.min_item=0;
    }
    $scope.div_trayek=true;
    $scope.div_komoditas=false;
    $scope.div_satuan=false;
    $scope.div_moda=true;
    $scope.div_armada=false;
    $scope.div_container=false;
    $scope.div_tarif_min=true;
    $scope.div_tarif=false;
    $scope.div_rack=false;
    $scope.div_storage_tonase=false;
    $scope.div_storage_volume=false;
    $scope.div_handling_tonase=false;
    $scope.div_handling_volume=false;
    $scope.div_warehouse=false;
} else if (service.service_type_id == 2) {
    $scope.formData.stype_id=2;
    $scope.div_trayek=true;
    $scope.div_komoditas=false;
    $scope.div_satuan=false;
    $scope.div_moda=false;
    $scope.div_armada=false;
    $scope.div_container=true;
    $scope.div_tarif_min=false;
    $scope.div_tarif=true;
    $scope.div_rack=false;
    $scope.div_storage_tonase=false;
    $scope.div_storage_volume=false;
    $scope.div_handling_tonase=false;
    $scope.div_handling_volume=false;
    $scope.div_warehouse=false;
} else if (service.service_type_id == 3) {
    $scope.formData.stype_id=3;
    $scope.div_trayek=true;
    $scope.div_komoditas=false;
    $scope.div_satuan=false;
    $scope.div_moda=false;
    $scope.div_armada=true;
    $scope.div_container=false;
    $scope.div_tarif_min=false;
    $scope.div_tarif=true;
    $scope.div_rack=false;
    $scope.div_storage_tonase=false;
    $scope.div_storage_volume=false;
    $scope.div_handling_tonase=false;
    $scope.div_handling_volume=false;
    $scope.div_warehouse=false;
} else if (service.service_type_id == 4) {
    $scope.formData.stype_id=4;
    $scope.div_trayek=true;
    $scope.div_komoditas=false;
    $scope.div_satuan=true;
    $scope.div_moda=false;
    $scope.div_armada=true;
    $scope.div_container=false;
    $scope.div_tarif_min=false;
    $scope.div_tarif=true;
    $scope.div_rack=false;
    $scope.div_storage_tonase=false;
    $scope.div_storage_volume=false;
    $scope.div_handling_tonase=false;
    $scope.div_handling_volume=false;
    $scope.div_warehouse=false;
} else if (service.service_type_id == 5) {
    $scope.formData.stype_id=5;
    $scope.div_trayek=false;
    $scope.div_komoditas=true;
    $scope.div_satuan=false;
    $scope.div_moda=false;
    $scope.div_armada=false;
    $scope.div_container=false;
    $scope.div_tarif_min=false;
    $scope.div_tarif=false;
    $scope.div_rack=false;
    $scope.div_storage_tonase=true;
    $scope.div_storage_volume=true;
    $scope.div_handling_tonase=false;
    $scope.div_handling_volume=false;
    $scope.div_warehouse=true;
} else if (service.service_type_id == 6) {
    $scope.formData.stype_id=6;
    $scope.div_trayek=false;
    $scope.div_komoditas=false;
    $scope.div_satuan=true;
    $scope.div_moda=false;
    $scope.div_armada=false;
    $scope.div_container=false;
    $scope.div_tarif_min=false;
    $scope.div_tarif=true;
    $scope.div_rack=false;
    $scope.div_storage_tonase=false;
    $scope.div_storage_volume=false;
    $scope.div_handling_tonase=false;
    $scope.div_handling_volume=false;
    $scope.div_warehouse=false;
} else if (service.service_type_id == 7){
    $scope.formData.stype_id=7;
    $scope.div_trayek=false;
    $scope.div_komoditas=false;
    $scope.div_satuan=false;
    $scope.div_moda=false;
    $scope.div_armada=false;
    $scope.div_container=false;
    $scope.div_tarif_min=false;
    $scope.div_tarif=true;
    $scope.div_rack=false;
    $scope.div_storage_tonase=false;
    $scope.div_storage_volume=false;
    $scope.div_handling_tonase=false;
    $scope.div_handling_volume=false;
    $scope.div_warehouse=false;
} else {
    $scope.formData.stype_id=$scope.service.service_type_id;
    $scope.div_trayek=false;
    $scope.div_komoditas=false;
    $scope.div_satuan=false;
    $scope.div_moda=false;
    $scope.div_armada=false;
    $scope.div_container=false;
    $scope.div_tarif_min=false;
    $scope.div_tarif=false;
    $scope.div_rack=false;
    $scope.div_storage_tonase=false;
    $scope.div_storage_volume=false;
    $scope.div_handling_tonase=false;
    $scope.div_handling_volume=false;
    $scope.div_warehouse=false;
}

}

    $scope.$watch('formData.service_id', function(){
        $scope.dom_switch($scope.formData.service_id, $scope.formData.company_id, $scope.isedit)
    })

$scope.disBtn=false;
$scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
        type: "post",
        url: baseUrl+'/marketing/price_list/'+$stateParams.id+'?_method=PUT&_token='+csrfToken,
        data: $scope.formData,
        success: function(data){
            $scope.$apply(function() {
                $scope.disBtn=false;
            });
            toastr.success("Data Berhasil Disimpan");
            $state.go('marketing.price_list');
        },
        error: function(xhr, response, status) {
            $scope.$apply(function() {
                $scope.disBtn=false;
            });
// console.log(xhr);
if (xhr.status==422) {
    var msgs="";
    $.each(xhr.responseJSON.errors, function(i, val) {
        msgs+='- '+val+'<br>';
    });
    toastr.warning(msgs,"Validation Error!");
} else {
    toastr.error(xhr.responseJSON.message,"Error has Found!");
}
}
});
}

$scope.minTypeChange = function() {
    if($scope.data.item.service_type_id == 1) {
        if($scope.formData.min_type == 1) {
            if($scope.data.item.min_type != 1) {
                $scope.formData.price_tonase = 0;
                $scope.formData.min_tonase = 0;
                $scope.formData.price_volume = 0;
                $scope.formData.min_volume = 0;
                $scope.formData.price_item = 0;
                $scope.formData.min_item = 0;
            }
        }
        else if($scope.formData.min_type == 2) {
            if($scope.data.item.min_type != 2) {
                $scope.formData.minimal_detail = [];
            }
        }
    }
}

$scope.InsertOrUpdate = 0;
// $scope.indexEdit = null;
$scope.minimDetailId = null;

$scope.addMinMultipleDetail = function () {
    // $scope.indexEdit = null;
    $scope.price_tonase = 0;
    $scope.min_tonase = 0;
    $scope.price_volume = 0;
    $scope.min_volume = 0;
    $scope.price_item = 0;
    $scope.min_item = 0;
    $scope.InsertOrUpdate = 0;
    $('#modalMinMultipleDetail').modal('show');
}

$scope.submitFormMinMultipleDetail = function () {
    preData = {
        // id: $scope.minimDetailId,
        price_list_id: $stateParams.id,
        price_per_kg: $scope.price_tonase,
        min_kg: $scope.min_tonase,
        price_per_m3: $scope.price_volume,
        min_m3: $scope.min_volume,
        price_per_item: $scope.price_item,
        min_item: $scope.min_item
    };
    var urlReq = '';
    if($scope.InsertOrUpdate == 0) {
        urlReq = baseUrl+'/marketing/price_list/minimum_detail?_token='+csrfToken;
    }
    else {
        urlReq = baseUrl+'/marketing/price_list/minimum_detail/'+$scope.minimDetailId+'?_method=PUT&_token='+csrfToken;
    }

    $.ajax({
        type: "post",
        url: urlReq,
        data: preData,
        success: function(data){
            // if($scope.InsertOrUpdate == 0) {
            //     $scope.formData.minimal_detail.push(preData);
            //     console.log('insert');
            // }
            // else {
            //     $scope.formData.minimal_detail[$scope.indexEdit] = preData;
            //     console.log('update');
            // }
        
            // $scope.indexEdit = null;
            // $scope.minimDetailId = null;
            // $scope.price_tonase = 0;
            // $scope.min_tonase = 0;
            // $scope.price_volume = 0;
            // $scope.min_volume = 0;
            // $scope.price_item = 0;
            // $scope.min_item = 0;
            // $scope.InsertOrUpdate = 0;
            $('#modalMinMultipleDetail').modal('hide');
            toastr.success("Data Berhasil Disimpan");
            $timeout(function() {
                $state.reload();
            },1000);
        },
        error: function(xhr, response, status) {
            if (xhr.status==422) {
                var msgs="";
                $.each(xhr.responseJSON.errors, function(i, val) {
                    msgs+='- '+val+'<br>';
                });
                toastr.warning(msgs,"Validation Error!");
            } else {
                toastr.error(xhr.responseJSON.message,"Error has Found!");
            }
        }
    });
}

$scope.deleteMinMultipleDetail = function (index) {
    var cofs=confirm("Apakah anda yakin ?");
    if (cofs) {
        $http.delete(baseUrl+'/marketing/price_list/minimum_detail/' + $scope.formData.minimal_detail[index].id + '?_token='+csrfToken).then(function(data) {
            // for( var i = 0; i < $scope.formData.minimal_detail.length; i++) { 
            //     if ( index === i) { 
            //         $scope.formData.minimal_detail.splice(i, 1); 
            //     }
            // }
            toastr.success("Data berhasil dihapus !");
            $state.reload();
        });
    }
}

$scope.editMinMultipleDetail = function (index) {
    // $scope.indexEdit = index;
    $scope.minimDetailId = $scope.formData.minimal_detail[index].id
    $scope.price_tonase = $scope.formData.minimal_detail[index].price_per_kg;
    $scope.min_tonase = $scope.formData.minimal_detail[index].min_kg;
    $scope.price_volume = $scope.formData.minimal_detail[index].price_per_m3;
    $scope.min_volume = $scope.formData.minimal_detail[index].min_m3;
    $scope.price_item = $scope.formData.minimal_detail[index].price_per_item;
    $scope.min_item = $scope.formData.minimal_detail[index].min_item;
    $scope.InsertOrUpdate = 1;
    $('#modalMinMultipleDetail').modal('show');
}
});
app.controller('marketingPriceListShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Detail Price List";
    $('.ibox-content').addClass('sk-loading');
    $scope.costData = {}
    $scope.units = {}
    $scope.cost_detail = []
    $scope.type_cost=[
        {id:1,name:'Biaya Operasional'},
        {id:2,name:'Reimbursement'},
    ]

    $scope.showHandlingType = function() {
            $http.get(baseUrl+'/setting/setting/handling_type').then(function(data){
                var handling_type = data.data[0].content.settings
                var chosen = handling_type.find(h => h.id == $scope.data.handling_type) 
                $scope.data.handling_type_name = chosen.name
            }, function() {
                $timeout(function(){
                    $scope.showHandlingType()
                }, 10000)
            })
    }

    $scope.showCost = function()  {

        $http.get(baseUrl+'/marketing/price_list/' + $stateParams.id + '/cost').then(function(data) {
            $scope.cost_detail=data.data;
        });
    }
    $scope.showCost()

    $scope.showCostType = function()  {
        $http.get(baseUrl+'/setting/cost_type').then(function(data) {
            $scope.cost_type=data.data;
        });
    }
    $scope.showCostType()

    $scope.changeCT=function(id) {
        $http.get(baseUrl+'/setting/cost_type/'+id).then(function(data) {
          $scope.cost_type_f=data.data.item;
          $scope.costData.vendor_id = parseInt($scope.cost_type_f.vendor_id);
          $scope.costData.qty=$scope.cost_type_f.qty;
          $scope.searchVendorPrice()
        });
    }

    $scope.calcCTTotalPrice=function(){
        $scope.costData.total_price=$scope.costData.qty*$scope.costData.price
    }

    $scope.addCost=function() {
        $scope.costData={};
        $scope.costData.is_edit=0;
        $scope.costData.type=1
        $scope.costData.cost_type = 144;
        $scope.costData.price=0
        $scope.costData.qty=1
        $scope.costData.total_price=0
        $scope.titleCost = 'Tambah Biaya'
        $('#modalCost').modal('show');
    }

    $scope.searchVendorPrice = function() {
    if($scope.costData.vendor_id && $scope.costData.cost_type) {
        var data = {
            cost_type_id : $scope.costData.cost_type,
            vendor_id : $scope.costData.vendor_id
        }
        $http.get(baseUrl+'/marketing/vendor_price/all/search?' + $.param(data)).then(function(data) {
            $scope.costData.price=data.data;
            $scope.calcCTTotalPrice()
        });
    }
  }

    $scope.submitCost=function() {
        $scope.disBtn=true;
        var method = 'post'
        if($scope.is_edit == 1) {
            method = 'put'            
        }
        $http[method](baseUrl+'/marketing/price_list/'+$stateParams.id+'/cost',$scope.costData).then(function(data) {
          // $state.go('operational.job_order');
          $('#modalCost').modal('hide');
          $timeout(function() {
            $state.reload();
          },1000)
          $scope.is_edit = 0
          toastr.success("Biaya berhasil disimpan!");
          $scope.disBtn=false;
        }, function(error) {
          $scope.is_edit = 0
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

    $scope.deleteCost=function(id) {
        var cofs=confirm("Apakah anda yakin ?");
        if (cofs) {
            $http.delete(baseUrl+'/marketing/price_list/' + $stateParams.id + '/cost/' + id).then(function(data) {
              toastr.success("Biaya tarif umum telah dihapus !");
              $state.reload();
            })
        }
      }

      $scope.editCost=function(id) {
            $scope.costData={};
            $scope.is_edit=1;
            $scope.costData.id=id;
            $scope.titleCost = 'Edit Biaya'
            $http.get(baseUrl+'/marketing/price_list/edit_cost/'+id).then(function(data) {
              var dt=data.data;
              // $scope.costData.cost_type=dt.cost_type_id;
              $scope.costData.cost_type=parseInt(dt.cost_type_id);
              $scope.costData.vendor_id=dt.vendor_id;
              $scope.costData.qty=dt.qty;
              $scope.costData.price=dt.price;
              $scope.costData.total_price=dt.total_price;
              $scope.costData.description=dt.description;
              $scope.costData.type=dt.type;
              $('#modalCost').modal('show');
            });
          }

    $scope.show = function() {
        $http.get(baseUrl+'/marketing/price_list/'+$stateParams.id).then(function(data) {
            $scope.data=data.data;
            $('.ibox-content').removeClass('sk-loading');
            $scope.showHandlingType()

            if($scope.data.service_type_id == 1 && $scope.data.min_type == 2) {
                $scope.showMinimumDetail();
            }
        });
    }
    $scope.show()

    $scope.showMinimumDetail = function() {
        $http.get(baseUrl+'/marketing/price_list/'+$stateParams.id+'/minimum_detail').then(function(data) {
            console.log(data);
            $scope.dataMinDetail=data.data;
            $('.ibox-content').removeClass('sk-loading');
        });
    }
});
