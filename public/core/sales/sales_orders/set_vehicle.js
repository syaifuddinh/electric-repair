salesOrders.controller('salesOrdersShowShipmentSetVehicle', function ($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter, salesOrdersService) {
    $scope.route_id = $stateParams.id
    $scope.shipment_id = $stateParams.id_shipment
    console.log("loaded", $scope.route_id)
    console.log("loaded", $scope.shipment_id)
    
    $('.ibox-content').addClass('sk-loading');
    $scope.formData={};
    $scope.formData.is_internal = 1;
    $scope.drivers=[]
    $scope.driver_eksternal=[]

    $(".clockpick").clockpicker({
        placement:'right',
        autoclose:true,
        donetext:'DONE',
    });


    $http.get(baseUrl+'/operational/manifest_ftl/create_delivery/'+$scope.shipment_id).then(function(data) {
        $scope.item=data.data.item;
        $scope.data=data.data;
        $scope.formData.code_manifest=$scope.item.code;
        $scope.formData.pick_date=dateNow;
        $scope.formData.pick_time=timeNow;
        $scope.formData.finish_date=dateNow;
        $scope.formData.finish_time=timeNow;
        $scope.driver_list($scope.formData);
        $('.ibox-content').removeClass('sk-loading');
    });

    $scope.backward = function() {
        $state.go('sales_order.sales_order.show.show_shipment',{id:$scope.route_id, id_shipment: $scope.shipment_id});
    }

    $scope.driver_list=function(fd) {
        $scope.drivers=[]
        if (fd.is_internal_driver) {
            angular.forEach($scope.driver, function(val,i) {
                if (val.is_internal) {
                    $scope.drivers.push({id:val.id,name:val.name})
                }
            })
        } else {
            if ($scope.formData.vendor_id) {
                angular.forEach($scope.driver,function(val,i) {
                    if (val.parent_id==$scope.formData.vendor_id) {
                        $scope.drivers.push({id:val.id,name:val.name})
                    }
                })
            } else {
                $scope.drivers=[]
            }
        }
        $scope.vehicles=[]
    }

    $scope.changeExtInt=function() {
        $scope.formData.vendor_id=null
        $scope.formData.driver_id=null
        $scope.formData.vehicle_id=null
        $scope.driver_list($scope.formData)
    }

    $scope.changeDriver=function(id) {

        var driver_eksternal = $scope.data.driver_eksternal.filter(x => x.parent_id == $scope.formData.vendor_id)
        $scope.driver_eksternal = driver_eksternal
        $scope.vehicles=[];
    }

    $scope.disBtn=false;

    $scope.submitForm=function() {
        $scope.disBtn=true;
        $http.post(baseUrl+'/operational/manifest_ftl/store_delivery/'+$scope.shipment_id,$scope.formData).then(function(data) {
            $timeout(function() {
                $state.go('sales_order.sales_order.show.show_shipment',{id:$scope.route_id, id_shipment: $scope.shipment_id});
            },1000)
            toastr.success("Data berhasil disimpan!");
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