warehouseReceipts.directive('warehouseReceiptsCreate', function(){
    return {
        restrict: 'E',
        scope : false,
        templateUrl : '/core/setting/operational_warehouse/warehouse_receipts/view/create.html',
        controller : function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, warehouseReceiptsService, $filter) {
        }
    }
});