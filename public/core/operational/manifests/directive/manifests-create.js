manifests.directive('manifestsCreate', function () {
    return {
        restrict: 'E',
        scope: {
            'source' :'=source',
            'sales_order_id' :'=salesOrderId',
            'indexRoute' :'=indexRoute',
            'indexRouteId' :'=indexRouteId',
            'isRedirectToDetail' :'=isRedirectToDetail',
            'hide_source' :'=hideSource'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/operational/manifests/view/manifests-create.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, manifestsService, additionalFieldsService) {
            $('.ibox-content').addClass('sk-loading');
            $scope.data = {}
            $scope.sources = []
            $scope.formData={}
            $scope.formData.company_id=compId;
            $scope.formData.etd_date=dateNow;
            $scope.formData.etd_time=timeNow;
            $scope.formData.eta_date=dateNow;
            $scope.formData.eta_time=timeNow;
            $scope.formData.date_manifest=dateNow;
            $scope.formData.reff_no='-';
            $scope.formData.description='-';
            $scope.formData.is_full=1;
            $scope.formData.vehicle_id=null;
            $scope.formData.driver_id=null;
            $scope.formData.source=null;


            if($scope.source) {
                $scope.formData.source = $scope.source
            }


            $http.get(baseUrl+'/operational/manifest_ftl/create').then(function(data) {
                $scope.data=data.data;
                $('.ibox-content').removeClass('sk-loading');
            });

            $scope.showSource = function() {
                $http.get(baseUrl+'/operational/manifest_ftl/source').then(function(resp) {
                    $scope.sources = resp.data.data
                }, function(){
                    $scope.showSource()
                });        
            }
            $scope.showSource()

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

            $scope.back = function() {
                if($rootScope.hasBuffer()) {
                    $rootScope.accessBuffer()
                } else {
                    $rootScope.emptyBuffer()
                    if($scope.indexRoute) {
                      if($scope.indexRouteId){
                        $state.go($scope.indexRoute, {"id" : $scope.indexRouteId})
                      } else {
                        $state.go($scope.indexRoute)
                      }
                    } else{
                        $state.go('operational.manifest_ftl')
                    }
                }
            } 

            $scope.disBtn=false;
            $scope.submitForm=function() {
                if($scope.sales_order_id) {
                    $scope.formData.sales_order_id = $scope.sales_order_id;
                }
              $scope.disBtn=true;
              $http.post(baseUrl+'/operational/manifest_ftl',$scope.formData).then(function(data) {
                toastr.success("Data berhasil disimpan!");
                $scope.disBtn=false;
                
                if($scope.isRedirectToDetail && $scope.isRedirectToDetail == true){
                  if($scope.source && $scope.source == 'sales_order'){
                    $state.go('sales_order.sales_order.show.show_shipment', { "id": $scope.indexRouteId, "id_shipment": data.data.id })
                  } else {
                    $scope.back()
                  }
                } else {
                  $scope.back()
                }
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
        }
    }
});