stocklistSales.controller('stocklistSales', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle = $rootScope.solog.label.stocklist.title

    $scope.add = function() {
        $rootScope.insertBuffer()
        $state.go('sales_order.stocklist.create')
    }
});