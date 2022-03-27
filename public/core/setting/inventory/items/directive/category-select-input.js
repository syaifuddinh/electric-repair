branchs.directive('categorySelectInput', function () {
    return {
        restrict: 'E',
        scope: {
            ngDisabled : '=ngDisabled'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/setting/general/items/view/category-select-input.html',
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
        controller: function ($scope, $http, $attrs, $rootScope, itemsService) {
            $scope.chosen = null
            $scope.list = []
            itemsService.api.indexCategory(function(list){
                $scope.list = list
            })
        }
    }
});