warehouseReceipts.directive('warehouseReceiptsEditButton', function () {
    return {
        restrict: 'E',
        scope: {
            id : '=id',
            type : '=?type',
        },
        require:'ngModel',
        templateUrl: '/core/inventory/warehouse_receipts/view/warehouse-receipts-edit-button.html',
        controller: function ($scope, $http, $attrs, $rootScope, $state) {  
            if(!$scope.id) {
                return false;
            }
            
            $scope.do = function() {
                $rootScope.insertBuffer()
                $state.go('operational_warehouse.receipt.edit', {id : $scope.id})
            }

            if(!$scope.type) {
                $scope.type = 'icon'
            }
        }
    }
});