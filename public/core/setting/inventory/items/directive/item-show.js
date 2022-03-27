items.directive('itemShow', function () {
    return {
        restrict: 'E',
        scope: {
            id : '=id',
            indexRoute : '=indexRoute',
            is_pallet : '=isPallet',
            is_container_part : '=isContainerPart',
            is_container_yard : '=isContainerYard'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/setting/inventory/items/view/item-show.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, itemsService) {
                $scope.item={}
                $scope.back=function() {
                    if($rootScope.hasBuffer()) {
                        $rootScope.accessBuffer()
                    } else {
                        $rootScope.emptyBuffer()
                        if($scope.indexRoute) {
                            $state.go($scope.indexRoute)
                        } else {
                            $state.go('inventory.item')
                        }
                    }
                }

                $scope.item_type=[
                    {id:1,name:'Internal'},
                    {id:2,name:'WH Items'},
                    {id:3,name:'Both'},
                ]
              $http.get(`${baseUrl}/inventory/item/${$stateParams.id}`).then(function(e) {
                let item_type = $scope.item_type.find(d => d.id>=e.data.item_type).name
                Object.assign($scope.item,e.data,{item_type:item_type})
              })
              $scope.pictures=[]
              $scope.getPictures=function() {
                $http.get(`${baseUrl}/inventory/item/get-pictures/${$stateParams.id}`).then(function(d) {
                  $scope.pictures=d.data.data
                })
              }
              $scope.getPictures()

              $scope.uploadFile=function(){
                let form = document.getElementById('fileForm')
                let data = new FormData(form)
                $http.post(`${baseUrl}/inventory/item/upload-picture/${$stateParams.id}`,data,{
                  cache: false,
                  contentType: false,
                  processData: false,
                  headers: { 'Content-Type': undefined, 'Accept' : 'application/json' }
                }).then(function(d) {
                  $scope.getPictures()
                }).catch(function(error) {
                  if (error.status==422) {
                    swal("Oops!",error.data.errors.file[0],"error")
                  } else {
                    swal("Oops!",error.data.message,"error")
                  }
                })
              }

        }
    }
});
