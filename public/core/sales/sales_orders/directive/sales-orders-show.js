salesOrders.directive('salesOrdersShow', function () {
    return {
        restrict: 'E',
        scope: {
            ngDisabled : '=ngDisabled',
            customerId : '=',
            is_multiple : '=isMultiple',
            index_route : '=indexRoute'
        },
        require:'ngModel',
        templateUrl: '/core/sales/sales_orders/view/sales-orders-show.html',
        controller: function ($scope, $http, $attrs, $rootScope, $timeout, $stateParams, $state, $compile, salesOrdersService) {
            $scope.job_order = {}

            $scope.formData = {}
            $scope.isShipment = true
            $scope.sales_order_id = $stateParams.id
            $rootScope.is_merchandise = 1

            $scope.show = function() {
                salesOrdersService.api.show($stateParams.id, function(dt) {
                    $scope.formData = dt
                    console.log(dt.status_slug == 'approved')
                    $rootScope.customer_id = null
                    $rootScope.job_order.showDetail(dt.job_order_id)
                    $rootScope.item = {}
                    $rootScope.item.show_add_button = true
                    $rootScope.item.show_total_price = true
                    $rootScope.item.service_type_id = 6
                    $rootScope.item.total_price = dt.total_price
                })
            }
            $scope.show()
            $scope.add = $rootScope.job_order.addItem
            $scope.job_order.addItem = function() {
                $scope.add()
                $rootScope.itemData.is_warehouse = 1
            }

            $scope.setApprove = function(){
                console.log('clicked APPROVE')
                salesOrdersService.api.approve($stateParams.id, function(resp){
                    var message = resp.message
                    toastr.success(message);
                    $scope.show()
                })
            }

            $scope.setReject = function(){
                console.log('clicked REJECT')
                salesOrdersService.api.reject($stateParams.id, function(resp){
                    var message = resp.message
                    toastr.success(message);
                    $scope.show()
                })
            }

            $scope.back = () => {
                if($scope.index_route) {
                    $state.go($scope.index_route);
                } else {
                    $state.go("sales_order.sales_order")
                }
            }

            $timeout(function(){
                $compile($('#modalStatus'))($scope)
            }, 300)
        }
    }
});