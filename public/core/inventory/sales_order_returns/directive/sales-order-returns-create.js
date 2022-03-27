salesOrderReturns.directive('salesOrderReturnsCreate', function () {
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
        templateUrl: '/core/inventory/sales_order_returns/view/sales-order-returns-create.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, salesOrderReturnsService) {
            $scope.formData={}
            $scope.formData.date_transaction=dateNow
            $scope.formData.detail=[]

            $scope.showDetail = function() {
                if($stateParams.id) {
                    salesOrderReturnsService.api.showDetail($stateParams.id, function(dt){
                        dt = dt.map(function(v){
                            v.item_name = v.name

                            return v
                        })
                        $scope.formData.detail = dt
                    })
                }
            }

            $scope.show = function() {
                if($stateParams.id) {
                    salesOrderReturnsService.api.show($stateParams.id, function(dt){
                        dt.date_transaction = $filter('minDate')(dt.date_transaction)
                        $scope.formData = dt
                        $scope.showDetail()
                    })
                }
            }
            $scope.show()

    $scope.appendItem = function(v) {
        var params = {}
        params.id = Math.round(Math.random() * 99999999)
        params.job_order_detail_id = v.id
        params.item_name = v.item_name
        params.sales_order_code = v.code
        params.qty_in_sales = v.qty

        $scope.formData.detail.push(params)
    }

    $scope.delete = function(id) {
        $scope.formData.detail = $scope.formData.detail.filter(x => x.id != id) 
    }

    $scope.$on('getSalesOrderDetail', function(e, v){
        $scope.appendItem(v)
    })

    $scope.back = function() {
        if($scope.index_route) {
            $state.go($scope.index_route)
        }  else {
            $state.go('operational_warehouse.pallet_sales_order_return');
        }
    }

    $scope.submitForm=function() {
        var method, url
        if($stateParams.id) {
            salesOrderReturnsService.api.update($scope.formData, $stateParams.id, function(){
                $scope.back()
            })
        } else {
            salesOrderReturnsService.api.store($scope.formData, function(){
                $scope.back()
            })
        }
    }
        }
    }
});