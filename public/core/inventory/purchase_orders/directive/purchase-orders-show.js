purchaseOrders.directive('purchaseOrdersShow', function () {
    return {
        restrict: 'E',
        scope: {
            index_route : '=indexRoute'
        },
        require:'ngModel',
        templateUrl: '/core/inventory/purchase_orders/view/purchase-orders-show.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, purchaseOrdersService) {
            $scope.totalHarga=0;
            $scope.formData = {}
            $('.ibox-content').toggleClass('sk-loading');

            $scope.openInfo = function() {
                $('.tab-item').hide()
                $('#info_detail').show()
            }

            $scope.openInfo()

            $scope.openReceipt = function() {
                $('.tab-item').hide()
                $('#receipt_detail').show()
                $scope.$broadcast('reloadWarehouseReceipt', 0)
            }

            $scope.backward = function() {
                if($scope.index_route) {
                    $state.go($scope.index_route)
                } else {
                    if($rootScope.hasBuffer()) {
                        $rootScope.accessBuffer()
                    } else {
                        $rootScope.emptyBuffer()
                        $state.go('inventory.purchase_order')
                    }
                }
            }

            $scope.show = function() {
                $http.get(baseUrl+'/inventory/purchase_order/'+$stateParams.id).then(function(data) {
                    $scope.data=data.data;
                    angular.forEach(data.data.detail,function(val,i) {
                        $scope.totalHarga+=val.total;
                    });
                    $('.ibox-content').removeClass('sk-loading');
                });
            }
            $scope.show()

            $scope.changePoDate = function() {
                $scope.po_date_edit = true
                $scope.formData.po_date = $filter('minDate')($scope.data.item.po_date)
            }

            $scope.abortPoDate = function() {
                $scope.po_date_edit = false
            }

            $rootScope.disBtn = false;
            $scope.approve = function() {
                $rootScope.disBtn = true;
                purchaseOrdersService.api.approve($stateParams.id, () => {
                    $scope.show()
                })
            }

            $scope.submitForm = function() {
                $scope.disBtn=true;
                $http.put(baseUrl+'/inventory/purchase_order/'+$stateParams.id+'?_token='+csrfToken,$scope.formData).then(function(data) {
                  $scope.disBtn=false;
                  $scope.abortPoDate()
                  $scope.show()
                }, function(error) {
                    $scope.disBtn=false;
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