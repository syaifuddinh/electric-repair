app.controller('operationalManifestFTL', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter, additionalFieldsService) {
    $rootScope.pageTitle="Packing List FTL";
});

app.controller('operationalManifestFTLCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Add Packing List";
});

app.controller('operationalManifestFTLShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter, additionalFieldsService) {
    $rootScope.pageTitle="Packing List FTL";
});
app.controller('operationalManifestFTLPickup', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Packing List FTL | Set Kendaraan";
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


    $http.get(baseUrl+'/operational/manifest_ftl/create_delivery/'+$stateParams.id).then(function(data) {
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
        $state.go('operational.manifest_ftl.show', {id : $stateParams.id})
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
        $http.post(baseUrl+'/operational/manifest_ftl/store_delivery/'+$stateParams.id,$scope.formData).then(function(data) {
            $timeout(function() {
                $state.go('operational.manifest_ftl.show',{id:$stateParams.id});
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

app.controller('operationalManifestFTLPickupEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Packing List FTL | Set Kendaraan";
    $('.ibox-content').addClass('sk-loading');
    $scope.formData={};
    $scope.formData.is_internal = 1;
    $scope.drivers=[]

    $(".clockpick").clockpicker({
      placement:'right',
      autoclose:true,
      donetext:'DONE',
    });

    $http.get(baseUrl+'/operational/manifest_ftl/edit_delivery/'+$stateParams.id).then(function(data) {
      $scope.item=data.data.item;
      $scope.data=data.data;
      $scope.vendor=data.data.vendor;
      $scope.driver=data.data.driver;
      $scope.formData = data.data.delivery;
      $scope.formData.delivery_order_number = $scope.formData.code;
      $scope.formData.code_manifest=$scope.item.code;
      $scope.formData.pick_date=dateNow;
      $scope.formData.pick_time=timeNow;
      $scope.formData.finish_date=dateNow;
      $scope.formData.finish_time=timeNow;
      $scope.formData.is_internal=$scope.item.is_internal_driver;
      $scope.formData.commodity_name=data.data.detail.item_name;
      if ($scope.item.is_internal_driver) {
        $scope.formData.driver_internal_id=$scope.formData.driver_id
        $scope.formData.vehicle_internal_id=$scope.formData.vehicle_id
      } else {
        $scope.formData.driver_eksternal_id=$scope.formData.driver_id
        $scope.formData.vehicle_eksternal_id=$scope.formData.vehicle_id
        if ($scope.formData.driver_id) {
          $scope.driverEksChange($scope.formData.driver_id)
        }
      }
      $scope.changeCustomer1($scope.formData.from_id);
      $scope.changeCustomer2($scope.formData.to_id);

      $('.ibox-content').removeClass('sk-loading');
    });

    $scope.driverEksChange=function(form) {
      $scope.vehicles=[]
      var dts = JSON.parse($rootScope.findJsonId(form,$scope.data.driver_eksternal).vehicle_list)
      angular.forEach($scope.data.vehicle_eksternal,function(v,i) {
        if ($rootScope.in_array(v.id,dts)) {
          $scope.vehicles.push({id:v.id,nopol:v.nopol})
        }
      })
    }

    $scope.driver_list=function(fd) {
      $scope.drivers=[]
      if (fd.is_internal) {
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

    $scope.changeCustomer1=function(id) {
      $scope.contact_address1=[];
      $http.get(baseUrl+'/operational/job_order/cari_address/'+id).then(function(data) {
        angular.forEach(data.data.address,function(val,i) {
          $scope.contact_address1.push(
            {id:val.id,name:val.name+', '+val.address}
          )
        });
      });
    }
    $scope.changeCustomer2=function(id) {
      $scope.contact_address2=[];
      $http.get(baseUrl+'/operational/job_order/cari_address/'+id).then(function(data) {
        angular.forEach(data.data.address,function(val,i) {
          $scope.contact_address2.push(
            {id:val.id,name:val.name+', '+val.address}
          )
        });
      });
    }
    $scope.changeDriver=function(id) {
      $scope.vehicles=[];
      $http.get(baseUrl+'/operational/manifest_ftl/cari_kendaraan/'+id).then(function(data) {
        angular.forEach(data.data,function(val,i) {
          $scope.vehicles.push(
            {id:val.vehicle_id,name:val.vehicle.nopol}
          )
        });
      });
    }

    $scope.disBtn=false;
    $scope.submitForm=function() {
      $scope.disBtn=true;
      $http.post(baseUrl+'/operational/manifest_ftl/update_delivery/'+$stateParams.id,$scope.formData).then(function(data) {
        // $state.go('operational.job_order');
        // $('#modalCost').modal('hide');
        $timeout(function() {
          $state.go('operational.manifest_ftl.show',{id:$stateParams.id});
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
