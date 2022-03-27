purchaseOrderReturs.directive('purchaseOrderRetursCreate', function(){
    return {
        restrict: 'E',
        scope : {
            index_route : "=indexRoute",
            is_merchandise : "=isMerchandise",
            is_pallet : "=isPallet"
        },
        templateUrl : '/core/inventory/purchase_order_returs/view/purchase-order-returs-create.html',
        controller : function($scope, $http,  $rootScope, $state, $stateParams, $timeout, $compile, $filter, purchaseOrderRetursService) {
            $scope.disBtn = false;
            $scope.formData={}
            $scope.formData.date_transaction=dateNow
            $scope.formData.detail=[]
            $scope.detailData={}
            $scope.detailData.qty=1
            $scope.detailData.stock=0
            $scope.formData.detail = {};
            $scope.warehouses = [];
            $scope.racks = [];

          
            $compile($('thead'))($scope)

            $scope.$on('getWarehouse', function(e, v){
                if(!$scope.formData.company_id) {
                    $scope.formData.company_id = parseInt(v.company_id)
                }
            })

            $scope.showDetail = function() {
                if($stateParams.id) {
                   purchaseOrderRetursService.api.showDetail($stateParams.id, function(dt){
                      dt = dt.map(function(v){
                            v.qty = v.qty_retur
                            v.code = v.item_code
                            v.name = v.item_name
                            return v
                      })
                      $scope.detail = dt
                   })
                }
            }

      $scope.show = function() {
          if($stateParams.id) {
               purchaseOrderRetursService.api.show($stateParams.id, function(dt){
                  dt.date_transaction = $filter('minDate')(dt.date_transaction)
                  $scope.formData = dt
                  $scope.detail = []
                  $scope.showDetail()
               })
          }
      }
      $scope.show()


        $scope.back = function() {
            if($scope.index_route) {
                $state.go($scope.index_route)
            } else {
                if($rootScope.hasBuffer()) {
                    $rootScope.accessBuffer()
                } else {
                    $rootScope.emptyBuffer()
                    $state.go('inventory.retur')
                }
            }
        }


  $scope.is_allow_insert = function() {
    if(!$scope.detailData.item_id || $scope.detailData.stock==0 || parseInt($scope.detailData.qty) > parseInt($scope.detailData.stock)) {
      return true;
    }

    return false;
  }

    $scope.deletes = function(id) {
        $scope.detail = $scope.detail.filter(x => x.id != id) 
    }

  
  $scope.disabledAppend = function() {
    if(!$scope.formData.detail.qty) {
      $scope.disabledAppendBtn = true;
    }
    else {
      console.log('Qty : ' + $scope.formData.detail.qty);
      console.log('Stock : ' + $scope.formData.detail.stock);
      if(parseInt($scope.formData.detail.qty) > parseInt($scope.formData.detail.stock) ) {
        $scope.disabledAppendBtn = true; 
      }
      else {
        $scope.disabledAppendBtn = false; 
      }
      console.log('d : ' + $scope.disabledAppendBtn);
    }
  }

  $scope.cariPallet=function() {
    if (!$scope.formData.warehouse_id) {
      toastr.error("Anda harus memilih gudang terlebih dahulu!","Maaf!")
      return null;
    }
    if (!$scope.formData.customer_id) {
      toastr.error("Anda harus memilih customer terlebih dahulu!","Maaf!")
      return null;
    }

    if (!$scope.formData.detail.warehouse_receipt_id) {
      toastr.error("Anda harus memilih No TTB terlebih dahulu!","Maaf!")
      return null;
    }
    $('#modalItem').modal()
    oTable.ajax.reload();
  }

  $scope.switchGudang = function() {
    var warehouses = [], unit;
    for(x in $scope.data.warehouse) {
      unit = $scope.data.warehouse[x];
      if(unit.company_id == $scope.formData.company_id) {
        warehouses.push(unit);
      }
    }

    $scope.warehouses = warehouses;
  }

  
  $scope.counter=0
  $scope.detail = []
  $scope.detailData = {}

  $scope.appendItemWarehouse = function(v) {
      $scope.detailData.code = v.code
      $scope.detailData.item_id = v.id
      $scope.detailData.name = v.name
      $scope.detailData.rack_id = v.rack_id
      $scope.detailData.warehouse_receipt_id = v.warehouse_receipt_id
      $scope.detailData.warehouse_receipt_detail_id = v.warehouse_receipt_detail_id
      $scope.detailData.warehouse_receipt_code = v.warehouse_receipt_code
      $scope.detailData.rack_code = v.rack_code
      $scope.appendTable()
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

  $scope.appendTable=function() {
    disabledAppendBtn = true;
    $scope.detailData.id = Math.round(Math.random() * 999999999)
    $scope.detail.push($scope.detailData)
    $scope.detailData = {};
    disabledAppendBtn = false;
    $scope.isItemExists = true;
  }

  $scope.resetDetail=function() {
    $scope.detailData={}
    $scope.detailData.qty=1
    $scope.detailData.stock=0
  }

  $scope.deleteAppend=function(id) {
    $scope.hitungAppend()
    console.log('Item length : ' + $scope.formData.detail.length)
    if($scope.formData.detail.length == 0) {
      $scope.isItemExists = false;
    }
  }

  $scope.submitForm=function() {
    var method = 'post'
    var url = purchaseOrderRetursService.url.store()
    if($stateParams.id) {
        method = 'put'
        url = purchaseOrderRetursService.url.update($stateParams.id)        
    }
    $scope.disBtn=true;
    var item_detail = $scope.detail;
    $scope.formData.detail = item_detail;

    $http[method](url,$scope.formData).then(function(data) {
      $scope.back()
      toastr.success("Data Berhasil Disimpan !");
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