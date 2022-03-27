purchaseOrderReturs.directive('purchaseOrderRetursShow', function(){
    return {
        restrict: 'E',
        scope : {
            index_route : "=indexRoute"
        },
        templateUrl : '/core/inventory/purchase_order_returs/view/purchase-order-returs-show.html',
        controller : function($scope, $http,  $rootScope, $state, $stateParams, $timeout, $compile, $filter, purchaseOrderRetursService) {
            $scope.show = function() {
                $http.get(baseUrl+'/inventory/retur/'+$stateParams.id).then(function(data) {
                    $scope.item=data.data.item;
                    $scope.detail=data.data.detail;
                    $scope.retur_receipt=data.data.retur_receipt;
                    $('.ibox-content').removeClass('sk-loading');
                });
            }
            $scope.show()

            $scope.back = function() {
                if($scope.index_route) {
                    $state.go($scope.index_route)
                } else {
                    if($rootScope.hasBuffer()) {
                        $rootScope.accessBuffer()
                    } else {
                        $rootScope.emptyBuffer()
                        $state.go('inventory.retur')
                    }
                }
            }

            $scope.approve = function() {
                is_confirm = confirm('Apakah anda yakin ?')
                if(is_confirm) {
                    $scope.disBtn = true
                    $http.put(baseUrl+'/inventory/retur/' + $stateParams.id + '/approve').then(function(data){
                        $scope.disBtn = false
                        toastr.success(data.data.message)
                        $scope.show()
                    },function(error){
                        $scope.disBtn = false
                        if (error.status==422) {
                            var det="";
                            angular.forEach(error.data.errors,function(val,i) {
                                det+="- "+val+"<br>";
                            });
                            toastr.warning(det,error.data.message);
                        } else {
                            toastr.error(error.data.message,"Error Has Found !");
                        }
                    })
                }
            }
        }
    }
});