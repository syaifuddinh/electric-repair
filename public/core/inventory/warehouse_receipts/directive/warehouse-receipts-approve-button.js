warehouseReceipts.directive('warehouseReceiptsApproveButton', function () {
    return {
        restrict: 'E',
        scope: {
            id : '=id',
            type : '=?type',
            onSubmit : '&'
        },
        require:'ngModel',
        templateUrl: '/core/inventory/warehouse_receipts/view/warehouse-receipts-approve-button.html',
        controller: function ($scope, $http, $attrs, $rootScope, $state, warehouseReceiptsService) {  
            if(!$scope.id) {
                return false;
            }
            
            $scope.do = function() {
                var is_confirm = confirm("Are you sure ?")
                if(is_confirm) {
                    warehouseReceiptsService.api.approve($scope.id, function(){
                        $scope.onSubmit()
                    })
                }
            }

            if(!$scope.type) {
                $scope.type = 'icon'
            }
        }
    }
});