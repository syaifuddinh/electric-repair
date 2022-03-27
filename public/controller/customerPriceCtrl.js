app.controller('marketingCustomerPrice', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Tarif Customer";
    $('.ibox-content').addClass('sk-loading');


    $scope.exportExcel = function() {
        var paramsObj = oTable.ajax.params();
        var params = $.param(paramsObj);
        var url = baseUrl + '/excel/customer_list_export?';
        url += params;
        location.href = url; 
    }


    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/customer/customer_price_datatable',
            dataSrc: function(d) {
                $('.ibox-content').removeClass('sk-loading');
                return d.data;
            }
        },
        columns:[
        { 
            data:null,
            render : function(resp) {
                if(resp.company) {
                    return resp.company.name;
                }
                else {
                    return '';
                }
            },
            name:"company.name"
        },
        {data:"customer.name",name:"customer.name"},
        {data:"route.name",name:"route.name", className:'hidden'},
        {data:"name",name:"name"},
        {data:"commodity.name",name:"commodity.name"},
        {data:"piece.name",name:"piece.name"},
        {data:"price_name",searchable : false, orderable : false},
        {data:"price_type_name",searchable : false, orderable : false},
        {data:"moda.name",name:"moda.name"},
        {data:"vehicle_type.name",name:"vehicle_type.name"},
        {data:"action_marketing",name:"action_marketing",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });

    $scope.deletes=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/marketing/customer_price/'+ids,{_token:csrfToken}).then(function(res) {
                toastr.success("Data Berhasil Dihapus!");
                oTable.ajax.reload();
            }, function(res) {
                toastr.error("Data Tidak dapat Dihapus!");
            });
        }
    }
});
app.controller('marketingCustomerPriceCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Tambah Tarif Customer";
    $scope.isedit=false;
    $scope.formData={};
    $('.ibox-content').addClass('sk-loading');
    $scope.labelWarehouse = '';
    $scope.detailData = {}

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
        var el = $(dom);
        var warehouse = $rootScope.findJsonId($scope.formData.service_id, $scope.data.service_warehouse);
        if(warehouse) {
            labelWarehouse = warehouse.name;
        }
        $scope.labelWarehouse = labelWarehouse.toLowerCase();
        console.log('Label : ' + labelWarehouse);

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
        $('#appendTable tbody').html('');
        $scope.formData.detail = []
        $scope.grandtotal = 0
        var service = $scope.data.service.find(x => x.id == $scope.formData.service_id)
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

        $http.get(baseUrl+'/marketing/customer_price/create').then(function(data) {
            $scope.data=data.data;
            $scope.container_types = data.data.container_type;
            $scope.type_1=data.data.type_1;
            $scope.type_2=data.data.type_2;
            $scope.type_3=data.data.type_3;
            $scope.type_4=data.data.type_4;
            $scope.type_5=data.data.type_5;
            $scope.type_6=data.data.type_6;
            $scope.type_7=data.data.type_7;
            $scope.formData.company_id=data.data.item.company_id;
            $('.ibox-content').removeClass('sk-loading');
        }, function(){
            $scope.create()
        });
    }
    $scope.create()
    $scope.div_tarif=false;
    $scope.dom_switch=function(ids,company,isedit) {
        $scope.formData.service_id=ids;
        $scope.formData.company_id=company;
        if ($scope.type_1.indexOf(ids)!==-1) {
            $scope.formData.stype_id=1;
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
            $scope.div_armada=true;
            $scope.div_container=false;
            $scope.div_tarif_min=true;
            $scope.div_tarif=false;
            $scope.div_rack=false;
            $scope.div_storage_tonase=false;
            $scope.div_storage_volume=false;
            $scope.div_handling_tonase=false;
            $scope.div_handling_volume=false;
        } else if ($scope.type_2.indexOf(ids)!==-1) {
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
        } else if ($scope.type_3.indexOf(ids)!==-1) {
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
        } else if ($scope.type_4.indexOf(ids)!==-1) {
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
        } else if ($scope.type_5.indexOf(ids)!==-1) {
            $scope.formData.stype_id=5;
            $scope.div_trayek=false;
            $scope.div_komoditas=true;
            $scope.div_satuan=false;
            $scope.div_moda=false;
            $scope.div_armada=false;
            $scope.div_container=false;
            $scope.div_tarif_min=false;
            $scope.div_tarif=false;
            $scope.div_rack=true;
            $scope.div_storage_tonase=true;
            $scope.div_storage_volume=true;
            $scope.div_handling_tonase=true;
            $scope.div_handling_volume=true;
        } else if ($scope.type_6.indexOf(ids)!==-1) {
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
        } else {
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
        }
    }

    $scope.disBtn=false;
    $scope.submitForm=function() {
        $scope.disBtn=true;
        $.ajax({
            type: "post",
            url: baseUrl+'/marketing/customer_price?_token='+csrfToken,
            data: $scope.formData,
            success: function(data){
                $scope.$apply(function() {
                    $scope.disBtn=false;
                });
                toastr.success("Data Berhasil Disimpan");
                $state.go('marketing.customer_price');
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

});
app.controller('marketingCustomerPriceEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Edit Tarif Customer";
    $scope.formData = {}
    $scope.isedit=false;
    $('.ibox-content').addClass('sk-loading');

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

    $scope.countTotal = function() {
        var prices = $scope.formData.detail.map(value => parseInt(value.price))
        prices = prices.filter(value => value || false)
        $scope.grandtotal = prices.reduce(
            (x, y) => x + y
            )

    }

    $scope.detailData = {}

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

    $scope.show = function() {

            $http.get(baseUrl+'/marketing/customer_price/'+$stateParams.id+'/edit').then(function(data) {
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
                $scope.formData={
                    company_id:dt.company_id,
                    price_type:dt.price_type,
                    combined_price_id:dt.combined_price_id,
                    customer_id:dt.customer_id,
                    min_tonase:dt.min_tonase,
                    price_tonase:dt.price_tonase,
                    min_volume:dt.min_volume,
                    price_volume:dt.price_volume,
                    min_item:dt.min_item,
                    price_item:dt.price_item,
                    price_full:dt.price_full,
                    route_id:dt.route_id,
                    commodity_id:dt.commodity_id,
                    name:dt.name,
                    piece_id:dt.piece_id,
                    service_id:dt.service_id,
                    moda_id:dt.moda_id,
                    vehicle_type_id:dt.vehicle_type_id,
                    description:dt.description,
                    price_handling_tonase:dt.price_handling_tonase,
                    price_handling_volume:dt.price_handling_volume,
                    rack_id:dt.rack_id,
                    container_type_id:dt.container_type_id,
                    stype_id:dt.service_type_id,
                    is_warehouse:dt.is_warehouse,
                    detail:[]
                }
                $scope.getServicePacket()
                $scope.dom_switch(dt.service_id,dt.company_id,true);
                if($scope.formData.container_type_id) {
                    var container = data.data.container_type.find(value => value.id == $scope.formData.container_type_id)
                    $scope.formData.size = $scope.sizes.find(size => size.value == container.size && size.unit == container.unit)
                    $scope.switchContainer();
                }
                if(dt.is_warehouse == 1) {
                    $scope.domSwitchWarehouse();
                }

                if(dt.combined_price_id) {
                    $scope.formData.stype_id = null
                    angular.forEach(dt.detail, function(value, index){
                        $scope.detailData.service = value.service
                        $scope.appendTableService()
                        $scope.formData.detail[index].price = value.price
                    })
                }
                $('.ibox-content').removeClass('sk-loading');
            }, function(){
                $scope.show()
            });
    }
    $scope.show()

    $scope.div_tarif=false;
    $scope.dom_switch=function(ids,company,isedit) {
// console.log(ids);
$scope.formData.service_id=ids;
$scope.formData.company_id=company;
$scope.formData.code=$scope.data.item.code;
$scope.formData.name=$scope.data.item.name;

if ($scope.type_1.indexOf(ids)!==-1) {
    $scope.formData.stype_id=1;
    if (isedit==false) {
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
    $scope.div_armada=true;
    $scope.div_container=false;
    $scope.div_tarif_min=true;
    $scope.div_tarif=false;
    $scope.div_rack=false;
    $scope.div_storage_tonase=false;
    $scope.div_storage_volume=false;
    $scope.div_handling_tonase=false;
    $scope.div_handling_volume=false;
} else if ($scope.type_2.indexOf(ids)!==-1) {
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
} else if ($scope.type_3.indexOf(ids)!==-1) {
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
} else if ($scope.type_4.indexOf(ids)!==-1) {
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
} else if ($scope.type_5.indexOf(ids)!==-1) {
    $scope.formData.stype_id=5;
    $scope.div_trayek=false;
    $scope.div_komoditas=true;
    $scope.div_satuan=false;
    $scope.div_moda=false;
    $scope.div_armada=false;
    $scope.div_container=false;
    $scope.div_tarif_min=false;
    $scope.div_tarif=false;
    $scope.div_rack=true;
    $scope.div_storage_tonase=true;
    $scope.div_storage_volume=true;
    $scope.div_handling_tonase=true;
    $scope.div_handling_volume=true;
} else if ($scope.type_6.indexOf(ids)!==-1) {
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
} else {
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
}
}

$scope.disBtn=false;
$scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
        type: "PUT",
        url: baseUrl+'/marketing/customer_price/'+$stateParams.id+'?_token='+csrfToken,
        data: $scope.formData,
        success: function(data){
            $scope.$apply(function() {
                $scope.disBtn=false;
            });
            toastr.success("Data Berhasil Disimpan");
            $state.go('marketing.customer_price');
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

});

app.controller('marketingCustomerPriceShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $http.get(baseUrl+'/marketing/customer_price/'+$stateParams.id).then(function(data) {
        $scope.item=data.data;
        if(data.data.detail.length > 0) {
            var detail = data.data.detail.map(x => x.price)
            $scope.grandtotal = detail.reduce((x, y) => x + y)
        }    
    });
});
