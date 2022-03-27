contracts.directive('contractModalInput', function () {
    return {
        restrict: 'E',
        scope: {
            customer_id : '=customerId',
            is_sales_contract : '=isSalesContract'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/marketing/contracts/view/contract-modal-input.html',
        link: function (scope, el, attr, ngModel) {
            if(!attr['ngModel'])
                return false

            scope.$on('chooseContract', function(e, v){
                ngModel.$setViewValue(v.id)
                var name = v.no_contract
                if(!name) {
                    name = v.code
                }
                if(name) {
                    scope.item_name = name + ' - ' + v.name
                }
                $('#contract_modal').modal('hide')
                scope.$emit('getContract', v)
            })
        },
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $timeout, contractsService) {
            $scope.chosen = null
            $scope.list = []
            $scope.formData = {}

            $scope.$watch('customer_id', function() {
                if($scope.customer_id)
                    $scope.formData.customer_id = $scope.customer_id
                    $scope.$broadcast('reloadContract', 1)

            })

            $scope.cariItem=function() {        
                $('#contract_modal').modal()
                $scope.$broadcast('reloadContract')
            }

            $scope.tableOnCreated = function (row, data) {
                var td = $(row).find('td:first-child')
                var val = td.text()
                var a = $('<a></a>')
                a.attr('ng-click', 'choose(' + data.id + ')')
                a.append(val)
                td.empty()
                td.append(a)
                $compile(td)($scope)
            }

            $scope.choose = function(id) {
                contractsService.api.show(id, function(dt){
                    $('#contract_modal').modal('hide')
                    $scope.$emit('chooseContract', dt)
                })
            }
        }
    }
});