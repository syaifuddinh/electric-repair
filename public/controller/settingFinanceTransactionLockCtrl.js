app.controller('settingFinanceTransactionLock', function($scope, $http, $rootScope,$state,$timeout,$compile,$filter) {
  $rootScope.pageTitle='Kunci Transaksi';
  $scope.isDisabled=false;

  $scope.editArea=function(ids) {
    $http.get(baseUrl+'/setting/transaction_lock/'+ids+'/edit').then(function success(data) {
      $scope.modalEditTitle='Edit Kunci Transaksi';
      $scope.urlArea=baseUrl+'/setting/transaction_lock/'+data.data.id;
      $scope.methodArea='PUT';

      $scope.name=data.data.name;
      $scope.last_date_lock=$filter('minDate')(data.data.last_date_lock);
      $('#modalEdit').modal('show');
    });
  }
  $scope.submitForm=function() {
    $scope.isDisabled=true;
    $.ajax({
      type: $scope.methodArea,
      url: $scope.urlArea,
      data: {
        last_date_lock: $scope.last_date_lock,
        _token:csrfToken,
      },
      dataType: "json",
      success: function(data){
        $scope.$apply(function() {
          $scope.isDisabled=false;
        });
        $('#modalEdit').modal('hide');
        oTable.ajax.reload();
        toastr.success("Data Berhasil Diperbarui!");
      },
      error: function(xhr,response,error){
        $scope.$apply(function() {
          $scope.isDisabled=false;
        });
        toastr.error("terjadi kesalahan saat memperbarui data!");
      }
    });
  };

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/setting/transaction_lock',
      data: function(d) {
        d.filterData=$scope.filterData;
      }
    },
    columns:[
      {data:"name",name:"name"},
      // {data:"last_date_lock",name:"last_date_lock",className:"text-center"},
      {data:"last_date_lock",name:"last_date_lock",className:"hidden"},
      {data:"action",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  })

});
app.controller('settingFinanceTransactionLockEdit', function($scope, $http, $rootScope) {
  $rootScope.pageTitle='Edit Kunci Transaksi';
  $scope.$state=$state;
});
