purchaseRequestSales.controller('purchaseRequestSales', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle = $rootScope.solog.label.purchase_request.title

    $scope.add = function() {
        $rootScope.insertBuffer()
        $state.go('sales_order.purchase_request.create')
    }
});