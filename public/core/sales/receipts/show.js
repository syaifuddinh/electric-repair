receiptSales.controller('receiptSalesShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, receiptSalesService) {
    $rootScope.pageTitle = $rootScope.solog.label.sales_order.title;
    $scope.formData = {}
    $scope.isShipment = true
    
    $scope.id = $stateParams.id
});