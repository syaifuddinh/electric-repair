app.controller('settingAccount', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Daftar Akun";
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
      filename: 'Akun',
      sheetName: 'Data',
      title: 'Akun',
      exportOptions: {
        rows: {
          selected: true
        }
      },
    }],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/account_datatable',
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"code",name:"code"},
      {data:"name",name:"name"},
      {data:"description",name:"description"},
      {data:"type",name:"type"},
      {data:"jenis",name:"jenis"},
      {data:"group_report",name:"group_report"},
      {data:"action",name:"action",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('setting.finance.account.edit')) {
          $(row).find('td').attr('ui-sref', 'setting.account.edit({id:' + data.id + '})')
          $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
          $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });

  oTable.buttons().container().appendTo('.ibox-tools')
  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/setting/account/'+ids,{_token:csrfToken}).then(function() {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function(err) {
        toastr.error("Sudah tercatat dalam transaksi.","Akun Tidak Dapat Dihapus!");
      });
    }
  }
});
app.controller('settingAccountCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah Akun";
  $('.ibox-content').addClass('sk-loading');

  $http.get(baseUrl+'/setting/account/create').then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.formData={
    is_base:0,
    group_report:1,
    jenis:1,
    no_cash_bank:0
  }

  $scope.backward = function() {
    if($rootScope.hasBuffer()) {
        $rootScope.accessBuffer()
    } else {
      $scope.emptyBuffer()
      $state.go('setting.account')
    }
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/account?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
            $state.go('setting.account');
        }
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
app.controller('settingAccountEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Edit Akun";
  $('.ibox-content').addClass('sk-loading');

  $http.get(baseUrl+'/setting/account/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data;
    $scope.formData={
      _token:csrfToken,
      _method:'PUT',
      is_base:data.data.item.is_base,
      group_report:data.data.item.group_report,
      jenis:data.data.item.jenis,
      code:data.data.item.code,
      name:data.data.item.name,
      type_id:data.data.item.type_id,
      no_cash_bank:data.data.item.no_cash_bank,
      company_id:data.data.item.company_id,
      is_cash_count:data.data.item.is_cash_count,
    }
    if (data.data.item.deep==1) {
      $scope.formData.category=data.data.item.parent_id;
    } else if (data.data.item.deep==2) {
      $http.get(baseUrl+'/setting/get_account/'+data.data.item.parent_id).then(function(res) {
        $scope.formData.category=res.data.parent_id;
        $scope.formData.sub_category=res.data.id;
      });
    } else if (data.data.item.deep==3) {
      $http.get(baseUrl+'/setting/get_account/'+data.data.item.parent_id).then(function(res) {
        $http.get(baseUrl+'/setting/get_account/'+res.data.parent_id).then(function(res2) {
          $scope.formData.category=res2.data.parent_id;
          $scope.formData.sub_category=res2.data.id;
          $scope.formData.sub_sub_category=res.data.id;
        });
      });
    }
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/account/'+$stateParams.id+'?_method=PUT&_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('setting.account');
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
