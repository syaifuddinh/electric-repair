items.directive('itemCreate', function () {
    return {
        restrict: 'E',
        scope: {
            id : '=id',
            indexRoute : '=indexRoute',
            is_pallet : '=isPallet',
            is_container_part : '=isContainerPart',
            is_merchandise : '=isMerchandise',
            hide_merchandise : '=hideMerchandise',
            is_container_yard : '=isContainerYard'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/setting/inventory/items/view/item-create.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, itemsService) {
            $rootScope.pageTitle = "Add Master Item / Jasa"
            $scope.formData = {}
            $scope.formData.is_merchandise=0
            $scope.formData.is_expired=0
            $scope.formData.is_service=0
            $scope.formData.item_type=1
            $scope.formData.is_stock=0
            $scope.formData.minimal_stock=0
            $scope.formData.long=0
            $scope.formData.wide=0
            $scope.formData.height=0
            $scope.formData.volume=0
            $scope.formData.tonase=0
            $scope.formData.std_purchase=0
            $scope.formData.harga_jual=0
            $scope.formData.harga_beli=0
            $scope.formData.is_accrual=1
            $scope.formData.is_bbm=0
            $scope.formData.is_operational=0
            $scope.formData.is_invoice=0
            $scope.formData.is_overtime=0
            $scope.formData.is_ppn=0
            $scope.formData.is_dangerous_good=0
            $scope.formData.is_fast_moving=0
            $scope.formError=[]

            if($scope.is_merchandise !== null && $scope.is_merchandise !== undefined) {
                $scope.formData.is_merchandise = $scope.is_merchandise
            }

            $scope.hideOption = function() {
                $scope.hide_detail = false
                $scope.hide_sku = false
                $scope.hide_barcode = false
                $scope.hide_piece_id = false
                $scope.hide_is_service = false
                $scope.hide_is_expired = false
                $scope.hide_is_internal = false
                $scope.hide_default_rack_id = false

                if($scope.is_container_yard || $scope.is_container_part) {
                    $scope.hide_detail = true
                    $scope.hide_sku = true
                    $scope.hide_barcode = true
                    $scope.hide_piece_id = true
                    $scope.hide_is_service = true
                    $scope.hide_is_expired = true
                    $scope.hide_is_internal = true
                    $scope.hide_default_rack_id = true
                }
            }
            $scope.hideOption()

            $scope.hitungVolume=function() {
                let long = parseFloat($scope.formData.long||0)
                let wide = parseFloat($scope.formData.wide||0)
                let height = parseFloat($scope.formData.height||0)
                $scope.formData.volume = long*wide*height;
            }

              $scope.$watch('formData.long',function(newData){
                $scope.hitungVolume()
              })
              $scope.$watch('formData.wide',function(newData){
                $scope.hitungVolume()
              })
              $scope.$watch('formData.height',function(newData){
                $scope.hitungVolume()
              })
              $scope.$watch('formData.is_operational',function(newData){
                if (newData==1) {
                  $scope.formData.is_invoice=0
                }
              })
  $scope.$watch('formData.is_invoice',function(newData){
    if (newData==1) {
      $scope.formData.is_operational=0
      $scope.formData.is_bbm=0
    }
  })
  $http.get(`${baseUrl}/inventory/item/create`).then(function(e) {
    $scope.data=e.data
  })

    $scope.show = function() {
        if($stateParams.id) {
            $http.get(`${baseUrl}/inventory/item/${$stateParams.id}/edit`).then(function(e) {
                $scope.data=e.data
                Object.assign($scope.formData, e.data.item)
            })
        }
    }
    $scope.show()

  $scope.back=function() {
    if($rootScope.hasBuffer()) {
        $rootScope.accessBuffer()
    } else {
        if($scope.indexRoute) {
            $state.go($scope.indexRoute)
        } else {
            $rootScope.emptyBuffer()
            $state.go('inventory.item')
        }
    }
  }

  $scope.store = function() {
    var url, method
    $scope.formError=[]
    $rootScope.disBtn = true
    if($stateParams.id) {
        method = 'put'
        url = `${baseUrl}/inventory/item/` + $stateParams.id
    } else {
        method = 'post'
        url = `${baseUrl}/inventory/item`
    }
    $http[method](url,$scope.formData).then(function(e) {
      $rootScope.disBtn = false
      toastr.success("Data Successfully Stored!");
      $scope.back()
    },function(error) {
      $rootScope.disBtn=false;
      if (error.status==422) {
        var det="";
        angular.forEach(error.data.errors,function(val,i) {
          det+="- "+val+"<br>";
        });
        toastr.warning(det,error.data.message);
      } else {
        toastr.error(error.data.message,"Error Has Found !");
      }
    })
  }
        }
    }
});
