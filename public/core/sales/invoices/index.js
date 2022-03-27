invoiceSales.controller('invoiceSales', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, invoiceSalesService) {
    $rootScope.pageTitle = $rootScope.solog.label.invoice.title

    $scope.add = function() {
        $rootScope.insertBuffer()
        $state.go('sales_order.invoice.create')
    }
});