receiptTypes.directive('receiptTypesSelectInput', function () {
    return {
        restrict: 'E',
        scope: {
            ngDisabled : '=ngDisabled'
        },
        require:'ngModel',
        template: "<solog-select ng-model='chosen' ng-change='get()' title='$root.solog.label.general.receipt_type' ng-disabled='ngDisabled' rows='list'></solog-select>",
        link: function (scope, el, attr, ngModel) {
            if(!attr['ngModel'])
                return false

            ngModel.$render = function () {
                scope.chosen = ngModel.$modelValue
            }
            scope.change = function() {
                ngModel.$setViewValue(scope.chosen)
            }

            scope.$on('download', function(e, v){
                scope.change()
            })
        },
        controller: function ($scope, $http, $attrs, $rootScope, receiptTypesService) {
            $scope.chosen = null
            $scope.list = []
            receiptTypesService.api.index(function(list){
                $scope.list = list
            })


            $scope.get = function() {
                receiptTypesService.api.show($scope.chosen, function(dt){
                    $scope.$emit('getReceiptType', dt)
                })
            }
        }
    }
});