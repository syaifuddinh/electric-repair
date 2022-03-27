purchaseOrderReturnSales.controller('purchaseOrderReturnSales', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle = $rootScope.solog.label.purchase_order_return.title

    $scope.add = function() {
        $rootScope.insertBuffer()
        $state.go('sales_order.purchase_order_return.create')
    }
});