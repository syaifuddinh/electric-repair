additionalFields.directive('additionalFieldsCreate', function () {
    return {
        restrict: 'E',
        scope: {
            formData : '=',
        },
        templateUrl: '/core/setting/general/additional_fields/view/create.html',
        controller: function ($scope, $http, $attrs, $rootScope, additionalFieldsService, fieldTypesService) {
            $scope.type_transactions = []
            $scope.field_types = []
            $rootScope.disBtn = false

            additionalFieldsService.api.indexGroup((list) => {
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