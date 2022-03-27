items.directive('itemModalInput', function () {
    return {
        restrict: 'E',
        scope: {
            ngDisabled : '=ngDisabled',
            customer_id : '=customerId',
            warehouse_id : '=warehouseId',
            is_container_part : '=isContainerPart',
            is_container_yard : '=isContainerYard',
            is_multiple : '=isMultiple'
        },
        require:'ngModel',
        templateUrl: '/core/setting/inventory/items/view/item-modal-input.html',
        link: function (scope, el, attr, ngModel) {
            if(!attr['ngModel'])
                return false

            ngModel.$render = function () {
                scope.show(ngModel.$modelValue)
            }

            scope.$on('chooseItem', function(e, v){
                el.find('.modal').modal('hide')
                ngModel.$setViewValue(v.id)
                scope.item_name = v.name
                scope.$emit('getItem', v)
            })
            scope.el = el
        },
        controller: function ($scope, $http, $attrs, $rootScope, $timeout, itemsService) {
            $scope.chosen = null
            $scope.list = []
            if($attrs.buttonLabel) {
                $scope.button_label = $attrs.buttonLabel
            }
            if(!$scope.button_label) {
                $scope.button_label = $rootScope.solog.label.general.add + ' ' + $rootScope.solog.label.general.item
            }

            $scope.show = function(id) {
                itemsService.api.show(id, function(dt){
                    $scope.item_name = dt.name
                })
            }

            $scope.adjustField = function() {
                $scope.hide_input = false
                $scope.hide_button = false

                if($attrs.type == 'button') {
                    $scope.hide_input = true
                } else {
                    $scope.hide_button = true
                }
            }
            $scope.adjustField()

            
            $scope.cariItem=function() {        
                console.log($scope.el)
                $scope.el.find('.modal').modal()
                $timeout(function() {
                    $('.modal-backdrop').removeClass('in')
                    $('.modal-backdrop').remove()
                }, 400)
            }


        }
    }
});