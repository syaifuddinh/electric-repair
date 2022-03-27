additionalFields.directive('additionalFieldsCreate', function () {
    return {
        restrict: 'E',
        scope: {
            formData : '='
        },
        templateUrl: '/core/setting/general/additional_fields/view/create.html',
        controller: function ($scope, $http, $attrs, $rootScope, additionalFieldsService, fieldTypesService, typeTransactionsService) {
            $scope.type_transactions = []
            $scope.type_transaction = {}
            $scope.field_types = []
            $rootScope.disBtn = false


            $scope.showTypeTransaction = function() {
                typeTransactionsService.api.show($scope.formData.type_transaction_id, function(dt){
                    $scope.type_transaction = dt
                })
            }

            $scope.$on('showTypeTransaction', function(e, v){
                $scope.showTypeTransaction()
            })    

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