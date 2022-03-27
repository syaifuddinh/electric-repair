contacts.directive('customerSelectInput', function () {
    return {
        restrict: 'E',
        scope: {
            ngDisabled : '=ngDisabled',
            joCustomerId : '='
        },
        transclude:true,
        require:'ngModel',
        template: "<solog-select ng-model='chosen' title='$root.solog.label.general.customer' ng-disabled='ngDisabled' rows='list'></solog-select>",
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
        controller: function ($scope, $http, $attrs, $rootScope, contactsService) {
            $scope.chosen = null
            $scope.list = []

            $scope.getListContacts = function(filled = false){
                if($scope.joCustomerId){
                    $http.get(baseUrl+'/operational/job_order/cari_address/'+$scope.joCustomerId).then(function(data) {
                        if(filled){
                            angular.forEach(data.data.address,function(val,i) {
                                var found = $scope.list.filter(x => x.id == val.id)
                                if(found.length < 1){
                                    $scope.list.push(
                                        {id:val.id,name:val.name+', '+val.address,collectible_id:val.contact_bill_id}
                                    )
                                }
                            });
                        } else {
                            angular.forEach(data.data.address,function(val,i) {
                                $scope.list.push(
                                    {id:val.id,name:val.name+', '+val.address,collectible_id:val.contact_bill_id}
                                )
                            });
                        }
                    });
                } else {
                    contactsService.api.indexCustomer(function(list){
                        $scope.list = list
                    })
                }
            }

            $scope.getListContacts()

            $scope.$on('savedContacts', function(data){
                $scope.getListContacts(true)
            })
        }
    }
});