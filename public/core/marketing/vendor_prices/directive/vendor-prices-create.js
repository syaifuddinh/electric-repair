vendorPrices.directive('vendorPricesCreate', function () {
    return {
        restrict: 'E',
        scope: {
            'index_route' :'=indexRoute',
            'index_params' :'=indexParams',
            'vendor_id' : '=vendorId',
            'hide_vendor' : '=hideVendor',
            'id' : '=id'
        },
        templateUrl: '/core/marketing/vendor_prices/view/vendor-prices-create.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, $timeout, contactsService) {
            $scope.cost_type = []
            $scope.isedit=false;
            $scope.formData={
                cost_category : 1
            };

            if($scope.vendor_id) {
                $scope.formData.vendor_id = parseInt($scope.vendor_id)
            }

            $scope.show = function() {
                  if($scope.id) {
                      $http.get(baseUrl+'/marketing/vendor_price/'+$scope.id+'/edit').then(function(data) {
                        $scope.data=data.data;
                        var dt=data.data.item;
                        $scope.formData={
                          company_id:dt.company_id,
                          vendor_id:dt.vendor_id,
                          price_full:dt.price_full,
                          route_id:dt.route_id,
                          name:dt.name,
                          piece_id:dt.piece_id,
                          service_id:dt.service_id,
                          moda_id:dt.moda_id,
                          vehicle_type_id:dt.vehicle_type_id,
                          description:dt.description,
                          container_type_id:dt.container_type_id,
                          cost_type_id: parseInt(dt.cost_type_id),
                          cost_category:dt.cost_category,
                          date:dt.date,
                        }
                        $('.ibox-content').removeClass('sk-loading');
                      }, function(){
                            $scope.show()
                      });
                  }
            }
            $scope.show()

            $scope.back = () => {
                if($scope.index_route) {
                    $state.go($scope.index_route, $scope.index_params)
                } else {
                    $state.go('marketing.vendor_price')
                }
            }

            $scope.showCostType = function() {
                $http.get(baseUrl+'/setting/cost_type').then(function(data) {
                    $scope.cost_type = data.data
                }, function(){
                    $scope.showCostType
                });
            }
            $scope.showCostType()

            
            $scope.disBtn=false;
            $scope.submitForm=function() {
                $scope.disBtn=true;
                var url
                if($scope.id) {
                    url = baseUrl+'/marketing/vendor_price/'+$scope.id+'?_method=PUT&_token='+csrfToken
                } else {
                    url = baseUrl+'/marketing/vendor_price?_token='+csrfToken
                }

                $.ajax({
                    type: "post",
                    url: url,
                    data: $scope.formData,
                    success: function(data){
                        $scope.$apply(function() {
                            $scope.disBtn=false;
                        });
                        toastr.success("Data Berhasil Disimpan");
                        $scope.back()
                    },
                    error: function(xhr, response, status) {
                        $scope.$apply(function() {
                            $scope.disBtn=false;
                        });

                        if (xhr.status==422) {
                            var msgs="";
                            $.each(xhr.responseJSON.errors, function(i, val) {
                                msgs+='- '+val+'<br>';
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