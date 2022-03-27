stockByItemSales.controller('stockByItemSales', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle = $rootScope.solog.label.general.stock_by_item

    $scope.add = function() {
        $rootScope.insertBuffer()
        $state.go('sales_order.stock_by_item.create')
    }
});