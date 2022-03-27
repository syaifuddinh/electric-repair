purchaseRequests.directive('purchaseRequestsShow', function () {
    return {
        restrict: 'E',
        scope: {
            warehouse_id : '=warehouseId',
            company_id : '=companyId',
            is_pallet : '=isPallet',
            is_merchandise : '=isMerchandise',
            index_route : '=indexRoute',
            tableOnCreated : '='
        },
        require:'ngModel',
        templateUrl: '/core/inventory/purchase_requests/view/purchase-requests-show.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, purchaseRequestsService) {
            $scope.formData={};
            $scope.qty_approve=false;
            $scope.price_po=false;
            $scope.editForm=false;
            $('.ibox-content').toggleClass('sk-loading');

            $scope.show = function() {
                $rootScope.disBtn=false;
                $scope.qty_approve=false;
                $scope.editForm=false;
                $scope.price_po=false;
                $http.get(baseUrl+'/inventory/purchase_request/'+$stateParams.id).then(function(data) {
                    $scope.data=data.data;
                    $scope.formData.detail = $scope.data.detail.map(v => {
                        v.qty_origin = v.qty
                        return v
                    })
                    $('.ibox-content').removeClass('sk-loading');
                });
            }
            $scope.show()

            $scope.status=[
                {id:0,name:'Permintaan Ditolak'},
                {id:1,name:'Belum Persetujuan'},
                {id:2,name:'Sudah Persetujuan'},
                {id:3,name:'Purchase Order'},
            ]

            $scope.editDetail=function(val) {
                $scope.detailData={}
                $scope.detailData.id=val.id
                $scope.detailData.vehicle_id=val.vehicle_id
                $scope.detailData.item_id=val.item_id
                $scope.detailData.qty=val.qty
                $('#modalEdit').modal()
            }

            $scope.approve=function() {
                $scope.qty_approve=true;
                $scope.editForm=true;
            }

            $scope.createPo=function() {
                $scope.price_po=true;
                $scope.editForm=true;
                $scope.formData.payment_type = 1
            }

            $scope.backward = function() {
                if($scope.index_route) {
                    $state.go($scope.index_route)
                } else {
                    if($rootScope.hasBuffer()) {
                        $rootScope.accessBuffer()
                    } else {
                        $rootScope.emptyBuffer()
                        $state.go('inventory.purchase_request')
                    }
                }
            }

            $scope.reject=function() {
                $scope.rejectData={}
                $('#modalReject').modal()
            }

            $rootScope.disBtn=false;
            $scope.approveSubmit=function() {
                $rootScope.disBtn=true;
                $http.post(baseUrl+'/inventory/purchase_request/approve/'+$stateParams.id+'?_token='+csrfToken,$scope.formData).then(function(data) {
                    $rootScope.disBtn=false;
                    $scope.show()
                });
            }

            $scope.deleteDetail=function(id) {
                var cofs=confirm("Apakah anda yakin ?");
                if (!cofs) {
                    return null;
                }
                
                $rootScope.disBtn=true;
                $http.delete(baseUrl+'/inventory/purchase_request/delete_detail/'+id).then(function(data) {
                    toastr.success("Detail Permintaan Telah Dihapus!")
                    $state.reload();
                });
            }
  $scope.rejectSubmit=function() {
    $rootScope.disBtn=true;
    $http.post(baseUrl+'/inventory/purchase_request/reject/'+$stateParams.id,$scope.rejectData).then(function(data) {
      $('#modalReject').modal('hide')
      toastr.success("Permintaan Telah ditolak!")
      $timeout(function() {
        $state.reload();
      },1000)
    });
  }
  $scope.submitDetail=function() {
    $rootScope.disBtn=true;
    $http.post(baseUrl+'/inventory/purchase_request/store_detail/'+$scope.detailData.id,$scope.detailData).then(function(data) {
        $rootScope.disBtn=false;
      $('#modalEdit').modal('hide')
      toastr.success("Data Detail Telah Diubah!")
      $timeout(function() {
        $state.reload();
      },1000)
    });
  }
  $scope.poSubmit=function() {
    $rootScope.disBtn=true;
    $http.post(baseUrl+'/inventory/purchase_request/create_po/'+$stateParams.id+'?_token='+csrfToken,$scope.formData).then(function(data) {
      $rootScope.disBtn=false;
      $scope.show()
    }, function(error) {
        $rootScope.disBtn=false;
        $rootScope.disBtn=false;
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