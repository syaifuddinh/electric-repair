branchs.directive('itemSelectInput', function () {
    return {
        restrict: 'E',
        scope: {},
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/setting/general/items/view/select-input.html',
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
            itemsService.api.index(function(list){
                $scope.list = list
            })
        }
    }
});