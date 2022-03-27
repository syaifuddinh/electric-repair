purchaseOrders.directive('purchaseOrdersModalInput', function () {
    return {
        restrict: 'E',
        scope: {
            company_id : '=companyId',
            is_merchandise : '=isMerchandise',
            po_status : '=poStatus'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/inventory/purchase_orders/view/purchase-orders-modal-input.html',
        link: function (scope, el, attr, ngModel) {
            if(!attr['ngModel'])
                return false

            scope.$on('choosePurchaseOrder', function(e, v){
                ngModel.$setViewValue(v.id)
                scope.item_name = v.code
                scope.$emit('getPurchaseOrder', v)
                $('#purchase_order_modal').modal('hide')
            })
        },
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $timeout, purchaseOrdersService) {
            $scope.chosen = null
            $scope.list = []
            $scope.formData = {}

            $scope.cariItem=function() {        
                $('#purchase_order_modal').modal()
                $scope.$broadcast('reloadPurchaseOrder', $scope.formData)
            }

            $scope.$watch('company_id', function() {
                if($scope.company_id) {
                    $scope.formData.company_id = $scope.company_id
                }
                else
                    return

            })

            $scope.$watch('is_approved', function() {
                if($scope.is_approved) {
                    $scope.formData.is_approved = $scope.is_approved
                }
                else
                    return

            })

            $scope.tableOnCreated = function (row, data) {
                var td = $(row).find('td:first-child')
                var val = td.text()
                var a = $('<a></a>')
                a.attr('ng-click', 'choose(' + data.id + ')')
                a.append(val)
                td.empty()
                td.append(a)
                $compile(td)($scope)

                td = $(row).find('td:nth-child(2)')
                val = td.text()
                a = $('<a></a>')
                a.attr('ng-click', 'choose(' + data.id + ')')
                a.append(val)
                td.empty()
                td.append(a)
            }

        }
    }
});