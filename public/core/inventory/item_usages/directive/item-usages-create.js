itemUsages.directive('itemUsagesCreate', function () {
    return {
        restrict: 'E',
        scope: {
            'isPallet' : '=isPallet',
            'index_route' : '=indexRoute'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/inventory/item_usages/view/item-usages-create.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, itemUsagesService) {
            $rootScope.disBtn = false
            $scope.formData={}
            $scope.formData.using_date=dateNow
            $scope.formData.detail=[]
            $scope.detailData={}
            $scope.detailData.qty=1
            $scope.detailData.stock=0

            $scope.back = function() {
                if($scope.index_route) {
                    $state.go($scope.index_route)
                } else {
                    $rootScope.emptyBuffer()
                    $state.go('inventory.using_item')
                }
            }

            $scope.showDetail = function() {
                if($stateParams.id) {
                    itemUsagesService.api.showDetail($stateParams.id, function(dt){
                        dt = dt.map(function(v){
                            v.code = v.item_code
                            v.name = v.item_name

                            return v
                        })
                        $scope.formData.detail = dt
                    })
                }
            }

            $scope.show = function() {
                if($stateParams.id) {
                    itemUsagesService.api.show($stateParams.id, function(dt){
                        $scope.formData = dt
                        $scope.showDetail()
                    })
                }
            }
            $scope.show()

            $scope.changeWarehouse=function(id) {
                $scope.formData.detail=[]
                $scope.detailData={}
                $scope.detailData.qty=1
                $scope.detailData.stock=0
                $scope.counter=0
            }

            $scope.appendItemWarehouse = function(v) {
                $scope.detailData = {}
                $scope.detailData.code = v.code
                $scope.detailData.item_id = v.id
                $scope.detailData.name = v.name
                $scope.detailData.rack_id = v.rack_id
                $scope.detailData.warehouse_receipt_id = v.warehouse_receipt_id
                $scope.detailData.warehouse_receipt_detail_id = v.warehouse_receipt_detail_id
                $scope.detailData.warehouse_receipt_code = v.warehouse_receipt_code
                $scope.detailData.rack_code = v.rack_code
                $scope.formData.detail.push($scope.detailData)
            }

            $scope.$on('getItemWarehouse', function(e, v){
                $scope.appendItemWarehouse(v)
            })

            $scope.$on('getItemWarehouses', function(e, items){
                var i
                for(i in items) {
                    $scope.appendItemWarehouse(items[i])
                }
            })

            $scope.deletes = function(id) {
                $scope.formData.detail = $scope.formData.detail.filter(x => x.id != id) 
            }

            $scope.resetDetail=function() {
                $scope.detailData={}
                $scope.detailData.qty=1
                $scope.detailData.stock=0
            }

            $scope.submitForm=function() {
                $rootScope.disBtn = true
                $compile($('[ng-click="submitForm()"]'))($rootScope)
                if($stateParams.id) {
                    itemUsagesService.api.update($scope.formData, $stateParams.id, function(){
                        $scope.back()
                    })
                } else {
                    itemUsagesService.api.store($scope.formData, function(){
                        $scope.back()
                    })            
                }
            }
        }
    }
});