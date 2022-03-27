app.controller('inventoryRetur', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Retur Barang";
});

app.controller('inventoryReturCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle = $rootScope.solog.label.general.add;
});

app.controller('inventoryReturShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Detail Retur Barang";
    $('.ibox-content').addClass('sk-loading');
});

app.controller('inventoryReturReceive', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Penerimaan Retur Barang";
  $scope.formData={};
  $scope.formData.date_receipt=dateNow;
  $('.ibox-content').addClass('sk-loading');

  $http.get(baseUrl+'/inventory/retur/receive/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $scope.detail=data.data.detail;
    $scope.warehouse=data.data.warehouse;
    $scope.formData.warehouse_id=$scope.item.warehouse_id;
    $scope.formData.detail=[];
    angular.forEach($scope.detail,function(val,i) {
      $scope.formData.detail.push(
        {
          detail_id:val.id,
          item_id:val.item_id,
          qty_retur:val.qty_retur,
          receive:val.receive,
          qty_terima:val.qty_retur-val.receive,
        }
      )
    });
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.cekLebih=function(qty_retur,qty_sudah,qty_terima) {
    if (qty_terima>(qty_retur-qty_sudah)) {
      $scope.disBtn=true;
      // toastr.error("Input yang anda masukkan lebih besar dari jumlah retur","Maaf!");
    } else {
      $scope.disBtn=false;
    }
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    var conf=confirm("Apakah data anda sudah sesuai ?");
    if (conf) {
      $scope.disBtn=true;
      $.ajax({
        type: "post",
        url: baseUrl+'/inventory/retur/store_receive/'+$stateParams.id+'?_token='+csrfToken,
        data: $scope.formData,
        success: function(data){
          $scope.$apply(function() {
            $scope.disBtn=false;
          });
          toastr.success("Data Berhasil Disimpan");
          $state.go('inventory.retur.show',{id:$stateParams.id});
          // oTable.ajax.reload();
        },
        error: function(xhr, response, status) {
          $scope.$apply(function() {
            $scope.disBtn=false;
          });
          // console.log(xhr);
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

});
