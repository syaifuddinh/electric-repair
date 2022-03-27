costRouteTypes.directive('costRouteTypesRadioInput', function () {
    return {
        restrict: 'E',
        scope: {
            ngDisabled : '=ngDisabled',
            type : '=type'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/setting/operational/cost_route_types/view/cost-route-types-radio-input.html',
        link: function (scope, el, attr, ngModel) {
            if(!attr['ngModel'])
                return false

            scope.change = function(self) {
                console.log(self)
                var id = $(self).attr('value')
                scope.chosen = id
                ngModel.$setViewValue(scope.chosen)
                scope.show(id)
                setTimeout(function () {
                    var el = $('[name="cost_route_type"][value="' + id + '"]')
                    if(el.length > 0) {
                        el.prop('checked', true) 
                    }
                }, 200)
            }

            ngModel.$render = function () {
                scope.chosen = parseInt(ngModel.$modelValue)
                setTimeout(function () {
                    scope.change($('[name="cost_route_type"][value="' + ngModel.$modelValue + '"]')[0])
                    var el = $('[name="cost_route_type"][value="' + scope.chosen + '"]')
                    if(el.length > 1) {
                        el[0].checked = true
                    }
                }, 600)
            }
            

            scope.$on('download', function(e, v){
                scope.change()
            })
        },
        controller: function ($scope, $http, $attrs, $rootScope, $compile, costRouteTypesService) {
            $scope.chosen = null
            $scope.list = []
            var params = {}
            
            costRouteTypesService.api.index(params, function(list){
                $scope.list = list

                $compile($('#cost_type_type_el'))($scope)
            })

            $scope.show = function(id) {
                costRouteTypesService.api.show(id, function(dt){
                    $scope.$emit('getCostRouteType', dt)
                })
            }
        }
    }
});