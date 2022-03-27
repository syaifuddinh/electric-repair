vendorPrices.directive('vendorPricesCreate', function () {
    return {
        restrict: 'E',
        scope: {
            formData : '='
        },
        templateUrl: '/core/setting/general/vendorPrices/view/create.html',
        controller: function ($scope, $http, $attrs, $rootScope, vendorPricesService, fieldTypesService) {
            $scope.type_transactions = []
            $scope.field_types = []
            $rootScope.disBtn = false

            vendorPricesService.api.indexGroup((list) => {
                $scope.type_transactions = list
            })

            fieldTypesService.api.index((list) => {
                $scope.field_types = list
            })

            $scope.show = function() {
            }
        }
    }
});