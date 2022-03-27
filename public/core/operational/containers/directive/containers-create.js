containers.directive('containersCreate', function () {
    return {
        restrict: 'E',
        scope: {
            'job_order_id' : '=jobOrderId'
        },
        templateUrl: '/core/operational/containers/view/containers-create.html',
        controller: function ($scope, $http, $attrs, $rootScope, $filter, $state, $stateParams, $timeout, $compile, containersService, salesOrdersService, additionalFieldsService) {    
            $rootScope.pageTitle = $rootScope.solog.label.general.container;
            $('.ibox-content').addClass('sk-loading');
            $http.get(baseUrl+'/operational/container/create').then(function(data) {
                $scope.data=data.data;
                $('.ibox-content').removeClass('sk-loading');
            });


              $scope.show = function() {
                  if($stateParams.id) {
                      $http.get(baseUrl+'/operational/container/'+$stateParams.id+'/edit').then(function(data) {
                          var dt=data.data.item;
                          $scope.formData.booking_date=$filter('minDate')(dt.booking_date);
                          $scope.formData.company_id=dt.company_id;
                          $scope.formData.voyage_schedule_id=dt.voyage_schedule_id;
                          $scope.current_voyage_schedule_id = dt.voyage_schedule_id
                          $scope.formData.booking_number=dt.booking_number;
                          $scope.formData.container_no=dt.container_no;
                          $scope.formData.container_type_id=dt.container_type_id;
                          $scope.formData.is_fcl=dt.is_fcl;
                          $scope.formData.seal_no=dt.seal_no;
                          $scope.formData.commodity_id=dt.commodity_id;
                          $scope.formData.commodity=dt.commodity;
                          if (dt.stripping) {
                            $scope.formData.stripping_time=$filter('aTime')(dt.stripping);
                            $scope.formData.stripping_date=$filter('minDate')(dt.stripping);
                          }
                          if (dt.stuffing) {
                            $scope.formData.stuffing_time=$filter('aTime')(dt.stuffing);
                            $scope.formData.stuffing_date=$filter('minDate')(dt.stuffing);
                          }
                          $('.ibox-content').removeClass('sk-loading');
                      }, function() {
                          $scope.show()
                      });
                  }
              }
              $scope.show()

              $scope.show()

            $scope.reset = function() {
                $scope.disBtn=false;
                $scope.formData={};
                $scope.formData.company_id=compId;
                $scope.formData.booking_date=dateNow;
                $scope.formData.is_fcl=1;
            }
            $scope.reset()

            $scope.backward = function() {
                if($rootScope.hasBuffer()) {
                    $rootScope.accessBuffer()
                } else {
                    $rootScope.emptyBuffer()
                    $state.go('operational.container')
                }
            }

            $scope.submitForm=function(exit = 1) {
                $scope.disBtn=true;
                $scope.formData.job_order_id = $scope.job_order_id
                var url, method
                if($stateParams.id && !$scope.job_order_id) {
                    url = baseUrl + '/operational/container/' + $stateParams.id
                    method = 'put'
                } else {
                    url = baseUrl + '/operational/container'
                    method = 'post'
                }
                $http[method](url,$scope.formData).then(function(data) {
                    if(exit == 1) {
                        $scope.backward()
                    } else {
                        $scope.reset()
                    }
                    toastr.success("Data Berhasil Disimpan!");
                    $scope.disBtn=false;
                }, function(error) {
                    $scope.disBtn=false;
                    if (error.status==422) {
                        var det="";
                        angular.forEach(error.data.errors,function(val,i) {
                        det+="- "+val+"<br>";
                        });
                        toastr.warning(det,error.data.message);
                    } else {
                        toastr.error(error.data.message,"Error Has Found !");
                    }
                });
            }
        }
    }
});