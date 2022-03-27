purchaseRequests.directive('purchaseRequestsCreate', function () {
    return {
        restrict: 'E',
        scope: {
            warehouse_id : '=warehouseId',
            company_id : '=companyId',
            is_pallet : '=isPallet',
            is_merchandise : '=isMerchandise',
            index_route : '=indexRoute',
            tableOnCreated : '='
        },
        require:'ngModel',
        templateUrl: '/core/inventory/purchase_requests/view/purchase-requests-create.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, purchaseRequestsService) {
            $scope.formData={};
            $scope.formData.detail=[];
            $scope.formData.date_request=dateNow;
            $scope.formData.date_needed=dateNow;
            $scope.formData.company_id=compId;
            $scope.formData.is_pallet=0;
            $scope.freeze = false;
            $('.ibox-content').addClass('sk-loading');

            $scope.detailData={};
            $scope.detailData.qty=0;
            $http.get(baseUrl+'/inventory/purchase_request/create').then(function(data) {
                $scope.data=data.data;
                $('.ibox-content').removeClass('sk-loading');
            });

            $scope.showDetail = function() {
                purchaseRequestsService.api.showDetail($stateParams.id, function(dt){
                    $scope.formData.detail = dt
                })
            }

            $scope.backward = function() {
                if($scope.index_route) {
                    $state.go($scope.index_route)
                } else {
                    $rootScope.emptyBuffer()
                    $state.go('inventory.purchase_request')
                }
            }

            $scope.show = function() {
                if($stateParams.id) {
                    purchaseRequestsService.api.show($stateParams.id, function(dt){
                        dt.supplier_id = parseInt(dt.supplier_id)
                        dt.date_request = $filter('minDate')(dt.date_request)
                        dt.date_needed = $filter('minDate')(dt.date_needed)
                        $scope.formData = dt
                        $scope.formData.po_date =$filter('minDate')(dt.po_date)
                        $scope.showDetail()
                    })
                }
            }
            $scope.show()

            $scope.showItems = function() {
                $scope.$broadcast('showItemsModal', 0)
            }

            $scope.urut=0;
            $scope.appendTable=function(v) {
                var id = Math.round(Math.random() * 9999999999)
                v.item_id = v.id
                v.id = id
                $scope.detailData = v
                v.item_name = v.name
                $scope.formData.detail.push(v)
                $scope.urut++;
                $scope.detailData={};
                $scope.detailData.qty=0;
                $scope.freeze = true;

                $scope.hitungDetail();
            }
            
            $scope.total=0;
            $scope.hitungDetail=function() {
                $scope.total=0;
                angular.forEach($scope.formData.detail, function(val,i) {
                    if (val) {
                        $scope.total+=parseFloat(val.qty);
                    }
                });
            }

            $scope.$on('getItem', function(e, v){
                $scope.appendTable(v)
            })
            $scope.$on('getItems', function(e, items){
                for(i in items) {
                    $scope.appendTable(items[i])
                }
            })

            $scope.deleteRow=function(id) {
                $scope.formData.detail = $scope.formData.detail.filter(x => x.id != id)
            }

            $compile($("[type='submit']"))($scope)
            $rootScope.disBtn=false;
            $scope.submitForm = function() {
              $rootScope.disBtn=true;
              if($stateParams.id) {

                  purchaseRequestsService.api.update($scope.formData, $stateParams.id, function(){
                        $scope.backward()
                  })
              } else {

                  purchaseRequestsService.api.store($scope.formData, function(){
                        $scope.backward()
                  })
              }
            }
        }
    }
});