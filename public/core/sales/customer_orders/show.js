customerOrders.controller('customerOrdersShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter, customerOrdersService) {
    $rootScope.pageTitle = $rootScope.solog.label.customer_order.title;
});