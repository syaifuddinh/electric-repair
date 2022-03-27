branchs.directive('itemCategorySelectInput', function () {
    return {
        restrict: 'E',
        scope: {
            ngDisabled : '=ngDisabled',
            is_pallet : '=isPallet',
            is_container_part : '=isContainerPart',
            is_container_yard : '=isContainerYard'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/setting/inventory/items/view/item-category-select-input.html',
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
            var payload = {}
            payload.is_container_part = $scope.is_container_part
            payload.is_container_yard = $scope.is_container_yard
            payload.is_pallet = $scope.is_pallet
            $scope.chosen = null
            $scope.list = []
            itemsService.api.indexCategory(payload, function(list){
                $scope.list = list
            })
        }
    }
});