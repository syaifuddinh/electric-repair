purchaseRequestSales.controller('purchaseRequestSalesShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, purchaseRequestSalesService) {
    $rootScope.pageTitle = $rootScope.solog.label.sales_order.title;
    $scope.formData = {}
    $scope.isShipment = true
    
    $scope.show = function() {
        purchaseRequestSalesService.api.show($stateParams.id, function(dt) {
            $scope.formData = dt
            $rootScope.customer_id = dt.customer_id
            $rootScope.job_order.showDetail(dt.job_order_id)
        })
    }
    $scope.show()
    $scope.add = $rootScope.job_order.addItem
    $scope.job_order.addItem = function() {
        $scope.add()
        $rootScope.itemData.is_warehouse = 1
    }

    $timeout(function(){
        $compile($('#modalStatus'))($scope)
    }, 300)
});