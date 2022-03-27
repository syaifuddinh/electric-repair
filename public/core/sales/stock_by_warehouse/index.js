stockByWarehouseSales.controller('stockByWarehouseSales', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle = $rootScope.solog.label.general.stock_by_warehouse

    $scope.add = function() {
        $rootScope.insertBuffer()
        $state.go('sales_order.stock_by_warehouse.create')
    }
});