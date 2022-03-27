operationalClaims.directive('operationalClaimsShow', function () {
    return {
        restrict: 'E',
        scope: {
            'source' :'=source'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/operational/claims/view/claims-show.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, operationalClaimsService) {
            $rootScope.pageTitle = "Klaim";
            $scope.isFilter = false;
            $scope.formData = {}
            $scope.formData.detail = []

            $scope.openJournal = function() {
                $rootScope.insertBuffer()
                $state.go('finance.journal.show', {id:$scope.formData.journal_id})
            }

            $scope.approve = function() {
                $scope.disBtn = true
                $http.put(baseUrl + '/operational/claims/' + $stateParams.id + '/approve').then(function(data) {
                $scope.disBtn = false
                toastr.success(data.data.message)
                $scope.show()
                }, function(xhr) {
                $scope.disBtn=false;
                    if (xhr.status==422) {
                        var msgs="";
                        $.each(xhr.data.errors, function(i, val) {
                        msgs+=val+'<br>';
                        });
                        toastr.warning(msgs,"Validation Error!");
                    } else {
                        toastr.error(xhr.data.message,"Error has Found!");
                    }
                });
            }

            $scope.countGrandtotal = function() {
                var grandtotal = 0
                for(d in $scope.formData.detail) {
                    D = $scope.formData.detail[d]
                    grandtotal += (D.qty * D.price)
                }
                $scope.formData.total = grandtotal
            }

            $scope.show = function() {
                    if($stateParams.id) {
                        $http.get(operationalClaimsService.url.show($stateParams.id)).then(function(data) {
                        var detail = $scope.formData.detail
                        $scope.formData = data.data;
                        $scope.formData.detail = detail
                        }, function() {
                            $scope.show()
                        });
                    }
            }
            $scope.show()

            $scope.showDetail = function() {
                    if($stateParams.id) {
                        $http.get(operationalClaimsService.url.showDetail($stateParams.id)).then(function(data) {
                        $scope.formData.detail = data.data
                        $scope.countGrandtotal()
                        }, function() {
                            $scope.showDetail()
                        });
                    }
            }
            $scope.showDetail()
        }
    }
})