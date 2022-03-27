contracts.directive('contractApprove', function () {
    return {
        restrict: 'E',
        scope: {
            'is_pegawai' :'=isPegawai',
            'is_pelanggan' :'=isPelanggan',
            'is_driver' :'=isDriver',
            'hide_type' :'=hideType',
            'detail_route' :'=detailRoute'
        },
        transclude:true,
        templateUrl: '/core/marketing/contracts/view/contract-approve.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, $timeout, contractsService) {
            $scope.formData={}
            $scope.formData.date_start_contract=dateNow;
            $scope.formData.date_end_contract=dateNow;
            $http.get(baseUrl+'/marketing/inquery/add_contract/'+$stateParams.id).then(function(data) {
                $scope.data=data.data;
                $scope.formData.description_contract=$scope.data.description_inquery;
            }, function(err) {
                toastr.error(err.data.message,"Oopps!");
                $state.go("marketing.inquery.show.detail",{id:$stateParams.id});
            });

            $scope.back = function() {
                if($scope.detail_route) {
                    $state.go($scope.detail_route, { id : $scope.data.id })
                } else {
                    $state.go("marketing.inquery.show", { id : $scope.data.id })
                }
            }

            $scope.disBtn=false;
            $scope.submitForm=function() {
                $scope.disBtn=true;
                $.ajax({
                    type: "post",
                    url: baseUrl+'/marketing/inquery/store_contract/'+$stateParams.id+'?_token='+csrfToken,
                    data: $scope.formData,
                    success: function(data){
                        $scope.$apply(function() {
                            $scope.disBtn=false;
                        });
                        toastr.success("Data Berhasil Disimpan","Selamat!");
                        $state.go('marketing.inquery.show.detail',{id:$stateParams.id});
                    },
                    error: function(xhr, response, status) {
                        $scope.$apply(function() {
                            $scope.disBtn=false;
                        });

                        if (xhr.status==422) {
                            var msgs="";
                            $.each(xhr.responseJSON.errors, function(i, val) {
                                msgs+=val+'<br>';
                            });
                            toastr.warning(msgs,"Validation Error!");
                        } else {
                            toastr.error(xhr.responseJSON.message,"Error has Found!");
                        }
                    }
                });
            }
        }
    }
});