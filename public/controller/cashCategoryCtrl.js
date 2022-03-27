app.controller('settingCashCategory', function($scope, $http, $rootScope,$state,$compile,$stateParams,$timeout) {
  $rootScope.pageTitle="Kategori Kas";
  $('.ibox-content').addClass('sk-loading');
  $http.get(baseUrl+'/setting/cash_category').then(function(data) {
    $scope.data=data.data;
  });

  $scope.formData={
    jenis:1
  };

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    dom: 'Blfrtip',
    buttons: [{
        extend: 'excel',
        enabled: true,
        action: newExportAction,
        text: '<span class="fa fa-file-excel-o"></span> Export Excel',
        className: 'btn btn-default btn-sm pull-right',
        filename: 'Route Pengangkutan',
        sheetName: 'Data',
        title: 'Route Pengangkutan',
        exportOptions: {
          rows: {
            selected: true
          }
        },
    }],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/cash_category_datatable',
      dataSrc: function(d) {
          $('.ibox-content').removeClass('sk-loading');
          return d.data;
      }
    },
    columns:[
      {data:"code",name:"code"},
      {data:"name",name:"name"},
      {data:"kategori_kas",name:"parent.name"},
      {data:"action",name:"action",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('setting.finance.cash_category.detail')) {
        $(row).find('td').attr('ui-sref', 'setting.cash_category.show({id:' + data.id + '})')
        $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
        $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });

  oTable.buttons().container().appendTo('.ibox-tools')

  var url;
  $scope.add=function() {
    $('#modal').modal();
    url=baseUrl+'/setting/cash_category?_token='+csrfToken;
  }

  $scope.edit=function(ids) {
    $http.get(baseUrl+'/setting/cash_category/'+ids+'/edit').then(function(data) {
      $scope.formData={
        code:data.data.code,
        name:data.data.name,
        parent_id:data.data.parent_id,
        jenis:data.data.jenis,
      }
      url=baseUrl+'/setting/cash_category/'+data.data.id+'?_token='+csrfToken+'&_method=PUT';
      $('#modal').modal();
    });
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: url,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        $('#modal').modal('hide');
        oTable.ajax.reload();
        toastr.success("Data Berhasil Disimpan");
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

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/setting/cash_category/'+ids,{_token:csrfToken}).then(function() {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      });
    }
  }
});

app.controller('settingCashCategoryShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout) {
  $rootScope.pageTitle="Detail Kategori Kas";
  $('.ibox-content').addClass('sk-loading');
  $http.get(baseUrl+'/setting/cash_category/'+$stateParams.id).then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.add=function() {
    $('#modal').modal('show');
  }

  $scope.submitForm=function() {
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/cash_category/store_detail/'+$stateParams.id+'?_token='+csrfToken,
      data: $scope.formData,
      dataType:'json',
      success: function(data){
        toastr.success("Data Berhasil Disimpan");
        $('#modal').modal('hide');
        $timeout(function() {
          $state.reload();
        },1000)
      },
      error: function(xhr,response,status) {
        toastr.error(xhr.responseJSON.message,"Error Has Found !");
      }
    });
  }

  var confs;
  $scope.delete=function(ids) {
    confs=confirm("Apakah anda ingin menghapus data ini ?");
    if (confs==true) {
      $.ajax({
        type: "post",
        url: baseUrl+'/setting/cash_category/delete_detail/'+ids+'?_token='+csrfToken,
        // data: $scope.formData,
        success: function(data){
          toastr.success("Data Berhasil Dihapus");
          $state.reload();
        },
        error: function(xhr,status,response) {
          toastr.error("Tidak dapat menghapus data karena sudah tersimpan di transaksi!","Error !");
        }
      });
    }
  }
});
