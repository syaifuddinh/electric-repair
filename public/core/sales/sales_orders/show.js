salesOrders.controller('salesOrdersShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter, salesOrdersService) {
    $rootScope.pageTitle = $rootScope.solog.label.sales_order.title;
});