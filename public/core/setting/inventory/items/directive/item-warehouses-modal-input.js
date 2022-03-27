items.directive('itemWarehousesModalInput', function () {
    return {
        restrict: 'E',
        scope: {
            ngDisabled : '=ngDisabled',
            quotationId : '=',
            customer_id : '=customerId',
            warehouse_id : '=warehouseId',
            isPallet : '=isPallet',
            is_multiple : '=isMultiple',
            is_merchandise : '=isMerchandise',
            show_sale_price : '=showSalePrice',
            show_jasa : '=showJasa',
            showPurchaseOrderCode : '=showPurchaseOrderCode',
            receiptTypeCode : '=receiptTypeCode'
        },
        require:'ngModel',
        templateUrl: '/core/setting/inventory/items/view/item-warehouses-modal-input.html',
        link: function (scope, el, attr, ngModel) {
            if(!attr['ngModel'])
                return false



            var slug = Math.round(Math.random() * 999999999) + '_modal'
            setTimeout(function () {
                $('#modal').attr('id', slug)
            }, 600)
            scope.slug = slug

            scope.$on('chooseItemWarehouse', function(e, v){
                $('#' + scope.slug).modal('hide')
                ngModel.$setViewValue(v.id)
                scope.item_name = v.name
                scope.$emit('getItemWarehouse', v)
            })

            if(scope.show_jasa) {
                scope.$on('chooseItem', function(e, v){
                    $('#' + scope.slug).modal('hide')
                    ngModel.$setViewValue(v.id)
                    scope.item_name = v.name
                    scope.$emit('getItem', v)
                })
            }

            scope.$on('chooseItemWarehouses', function(e, v){
                $('#' + scope.slug).modal('hide')
                scope.$emit('getItemWarehouses', v)
            })

            ngModel.$render = function () {
                var value = ngModel.$modelValue
                scope.show(value)
            }
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
            

            $scope.openItem = function() {
                $('.tab-item').hide()
                $('#item_detail').show()
            }


            $timeout(() => {
                $scope.openItem()
            }, 1000)
            
            $scope.openService = function() {
                $('.tab-item').hide()
                $('#service_detail').show()
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

            $scope.adjustWatch = function() {
                if($attrs.warehouseId) {
                    $scope.$watch('warehouse_id', function(){
                        var params = {}
                        params.warehouse_id = $scope.warehouse_id
                        $timeout(function(){
                            $scope.$broadcast('reloadItemWarehouses', params)
                        }, 400)
                    })
                }

                if($attrs.showPicking) {
                    var params = {}
                    params.show_picking = $attrs.showPicking
                    $timeout(function(){
                        $scope.$broadcast('reloadItemWarehouses', params)
                    }, 400)
                }
                if($scope.quotationId){
                    $scope.$watch('quotationId', function() {
                        var params = {}
                        params.quotation_id = $scope.quotationId
                        $timeout(function(){
                            $scope.$broadcast('reloadItemWarehouses', params)
                        }, 400)
                    })
                }
            }

            $scope.adjustWatch()

            $scope.cariItem=function() {        
                var modal = $('#' + $scope.slug)
                if($('#' + $scope.slug).length == 0){
                    $('#modal').attr('id', $scope.slug)
                    modal = $('#' + $scope.slug)
                }
                modal.modal()
                
                $scope.$broadcast('reloadItemWarehouses', 0)
                $timeout(function() {
                    $('.modal-backdrop').removeClass('in')
                    $('.modal-backdrop').remove()
                }, 400)
            }

            $scope.show = function(id) {
                itemsService.api.show(id, function(dt){
                    $scope.item_name = dt.name
                })
            }

        }
    }
});