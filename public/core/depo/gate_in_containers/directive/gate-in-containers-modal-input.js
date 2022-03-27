gateInContainers.directive('gateInContainersModalInput', function () {
    return {
        restrict: 'E',
        scope: {
            ngDisabled : '=ngDisabled',
            customer_id : '=customerId',
            warehouse_id : '=warehouseId',
            hide_input : '=hideInput',
            hide_button : '=hideButton',
            is_multiple : '=isMultiple'
        },
        require:'ngModel',
        templateUrl: '/core/depo/gate_in_containers/view/gate-in-containers-modal-input.html',
        link: function (scope, el, attr, ngModel) {
            if(!attr['ngModel'])
                return false

            ngModel.$render = function () {
                scope.show(ngModel.$modelValue)
            }

            scope.$on('chooseGateInContainer', function(e, v){
                el.find('.modal').modal('hide')
                ngModel.$setViewValue(v.id)
                scope.item_name = v.code
                scope.$emit('getGateInContainer', v)
            })
            scope.el = el
        },
        controller: function ($scope, $http, $attrs, $rootScope, $timeout, gateInContainersService) {
            $scope.chosen = null
            $scope.list = []
            if($attrs.buttonLabel) {
                $scope.button_label = $attrs.buttonLabel
            }
            if(!$scope.button_label) {
                $scope.button_label = $rootScope.solog.label.general.add + ' ' + $rootScope.solog.label.general.item
            }

            $scope.show = function(id) {
                if(id) {
                    gateInContainersService.api.show(id, function(dt){
                        $scope.item_name = dt.code
                    })
                }
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