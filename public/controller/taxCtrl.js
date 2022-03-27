app.controller('settingTax', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Pajak";
  $('.ibox-content').addClass('sk-loading');
  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    ordering: false,
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    dom: 'Blfrtip',
    buttons: [{
        extend: 'excel',
        enabled: true,
        action: newExportAction,
        text: '<span class="fa fa-file-excel-o"></span> Export Excel',
        className: 'btn btn-default btn-sm pull-right',
        filename: 'Pajak',
        sheetName: 'Data',
        title: 'Pajak',
        exportOptions: {
          rows: {
            selected: true
          }
        },
    }],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/tax_datatable',
      dataSrc: function(d) {
          $('.ibox-content').removeClass('sk-loading');
          return d.data;
      }
    },
    columns:[
      {data:"code",name:"code"},
      {data:"name",name:"name"},
      {data:"non_npwp",name:"non_npwp"},
      {data:"npwp",name:"npwp"},
      {data:"akun_pembelian",name:"akun1.name"},
      {data:"akun_penjualan",name:"akun2.name"},
      {data:"action",name:"action",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  oTable.buttons().container().appendTo('.ibox-tools')

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/setting/tax/'+ids,{_token:csrfToken}).then(function() {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      });
    }
  }
});
app.controller('settingTaxCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah Pajak";
  $('.ibox-content').addClass('sk-loading');
  $http.get(baseUrl+'/setting/tax/create').then(function(data) {
    
    $scope.data=data.data;
    $scope.formData.pemotong_pemungut=1;
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/tax?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('setting.tax');
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

});
app.controller('settingTaxEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Edit Pajak";
  $('.ibox-content').addClass('sk-loading');
  $http.get(baseUrl+'/setting/tax/'+$stateParams.id+'/edit').then(function(data) {
    
    $scope.data=data.data;
    $scope.formData={
      code:data.data.item.code,
      name:data.data.item.name,
      pemotong_pemungut:data.data.item.pemotong_pemungut,
      akun_pembelian:data.data.item.akun_pembelian,
      akun_penjualan:data.data.item.akun_penjualan,
      non_npwp:data.data.item.non_npwp,
      npwp:data.data.item.npwp,
      is_default:parseInt(data.data.item.is_default),
      is_ppn:parseInt(data.data.item.is_ppn),
    }
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/tax/'+$stateParams.id+'?_method=PUT&_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('setting.tax');
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

});
