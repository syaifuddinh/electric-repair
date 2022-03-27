contracts.directive('contractsCreate', function () {
    return {
        restrict: 'E',
        scope: {
            formData : '='
        },
        templateUrl: '/core/setting/general/contracts/view/create.html',
        controller: function ($scope, $http, $attrs, $rootScope, contractsService, fieldTypesService) {
            $scope.type_transactions = []
            $scope.field_types = []
            $rootScope.disBtn = false

            contractsService.api.indexGroup((list) => {
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