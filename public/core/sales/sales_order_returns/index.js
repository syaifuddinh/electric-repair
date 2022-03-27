salesOrderReturnSales.controller('salesOrderReturnSales', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle = $rootScope.solog.label.sales_order_return.title

    $scope.add = function() {
        $rootScope.insertBuffer()
        $state.go('sales_order.sales_order_return.create')
    }
});