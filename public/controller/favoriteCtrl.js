app.controller('settingFavorite', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Transaksi Favorit";
  $('.ibox-content').addClass('sk-loading');

  $http.get(baseUrl+'/setting/favorite').then(function(data) {
    $scope.data=data.data;
  });

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
        filename: 'Transaksi Favorit',
        sheetName: 'Data',
        title: 'Transaksi Favorit',
        exportOptions: {
          rows: {
            selected: true
          }
        },
    }],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/favorite_datatable',
      data: function(d) {
        d.filterData=$scope.filterData;
      },
      dataSrc: function(d) {
          $('.ibox-content').removeClass('sk-loading');
          return d.data;
      }
    },
    columns:[
      {data:"name",name:"name"},
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
      $http.delete(baseUrl+'/setting/favorite/'+ids,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

  $scope.refreshTable=function() {
    oTable.ajax.reload();
  }

  $scope.approve=function() {
    // console.log($scope.checkData);
    $http.post(baseUrl+'/setting/favorite/approve?_token='+csrfToken,$scope.checkData).then(function(data) {
      toastr.success("Jurnal Telah Disetujui","Berhasil!");
      $state.reload();
    }, function functionName(err) {
      toastr.error(err.data.message,"Error Has Found!");
    });
  }

  $scope.approvePost=function() {
    // console.log($scope.checkData);
    $http.post(baseUrl+'/setting/favorite/approve_post?_token='+csrfToken,$scope.checkData).then(function(data) {
      toastr.success("Jurnal Telah Diposting","Berhasil!");
      $state.reload();
    }, function functionName(err) {
      toastr.error(err.data.message,"Error Has Found!");
    });
  }

});
app.controller('settingFavoriteShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Jurnal Umum";
  $('.ibox-content').addClass('sk-loading');

  $http.get(baseUrl+'/setting/favorite/'+$stateParams.id).then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').removeClass('sk-loading');
  });
});
app.controller('settingFavoriteCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah Transaksi Favorit";
  $('.ibox-content').addClass('sk-loading');

  $http.get(baseUrl+'/setting/favorite/create').then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.jenis=[
    {id:1,name:"Debet"},
    {id:2,name:"Kredit"},
  ];


  $scope.formData={
    company_id:compId
  }
  var html="";
  var urutan=0;
  $scope.account=[];
  $scope.append=function() {
    html="";
    html+="<tr id='row-"+urutan+"'>";
    html+="<td><select class=\"form-control\" ng-model='formData.account_id["+urutan+"]' data-placeholder-text-single=\"'Pilih Akun'\" chosen allow-single-deselect=\"false\" data-placeholder=\"Pilih Header Akun\" ng-options=\"s.code+' - '+s.name group by s.parent.name for s in data.account\"><option value=''></option></select></td>";
    html+="<td><select class=\"form-control\" ng-model='formData.cash_category_id["+urutan+"]' ng-disabled=\"formData.account_id["+urutan+"].type.id!==1\" data-placeholder-text-single=\"'Pilih Kategori Kas'\" chosen allow-single-deselect=\"false\" data-placeholder=\"Pilih Header Akun\" ng-options=\"s.id as s.name group by s.category.name for s in data.cash_category\"><option value=''></option></select></td>";
    html+="<td><select class=\"form-control\" ng-model='formData.jenis["+urutan+"]' ng-init='formData.jenis["+urutan+"]=1' data-placeholder-text-single=\"'Debet/Kredit'\" chosen allow-single-deselect=\"false\" ng-options=\"s.id as s.name for s in jenis\"><option value=''></option></select></td>";
    html+="<td><a ng-click='hapus("+urutan+")' class='btn btn-sm btn-rounded btn-danger'>Delete</td>";
    html+="</tr>";

    $('#appendTable tbody').append($compile(html)($scope));
    urutan++;
  }

  $scope.hapus=function(ids) {
    $('#row-'+ids).remove();
    delete $scope.formData.account_id[ids];
    delete $scope.formData.jenis[ids];
    delete $scope.formData.cash_category_id[ids];
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/favorite?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('setting.favorite');
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
app.controller('settingFavoriteEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Edit Transaksi Favorit";
  $('.ibox-content').addClass('sk-loading');

  var urutan=0;
  $scope.cash_list=[];

  $http.get(baseUrl+'/setting/favorite/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data;
    $scope.formData={
      name:data.data.item.name
    }
    // urutan=data.data.item.details.length();
    angular.forEach(data.data.item.details, function(i,val) {
      urutan++;
    });

    angular.forEach(data.data.account, function(val,i) {
      if (val.type.id==1) {
        $scope.cash_list.push(val.id);
      }
    });
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.jenis=[
    {id:1,name:"Debet"},
    {id:2,name:"Kredit"},
  ];

  var html="";
  $scope.account=[];
  $scope.append=function() {
    html="";
    html+="<tr id='row-"+urutan+"'>";
    html+="<td><select class=\"form-control\" ng-model='formData.account_id["+urutan+"]' data-placeholder-text-single=\"'Pilih Akun'\" chosen allow-single-deselect=\"false\" data-placeholder=\"Pilih Header Akun\" ng-options=\"s.id as s.code+' - '+s.name group by s.parent.name for s in data.account\"><option value=''></option></select></td>";
    html+="<td><select class=\"form-control\" ng-model='formData.cash_category_id["+urutan+"]' ng-disabled=\"cash_list.indexOf(formData.account_id["+urutan+"])===-1\" data-placeholder-text-single=\"'Pilih Kategori Kas'\" chosen allow-single-deselect=\"false\" data-placeholder=\"Pilih Header Akun\" ng-options=\"s.id as s.name group by s.category.name for s in data.cash_category\"><option value=''></option></select></td>";
    html+="<td><select class=\"form-control\" ng-model='formData.jenis["+urutan+"]' ng-init='formData.jenis["+urutan+"]=1' data-placeholder-text-single=\"'Debet/Kredit'\" chosen allow-single-deselect=\"false\" ng-options=\"s.id as s.name for s in jenis\"><option value=''></option></select></td>";
    html+="<td><a ng-click='hapus("+urutan+")' class='btn btn-sm btn-rounded btn-danger'>Delete</a></td>";
    html+="</tr>";

    $('#appendTable tbody').append($compile(html)($scope));
    urutan++;
  }

  $scope.hapus=function(ids) {
    $('#row-'+ids).remove();
    delete $scope.formData.account_id[ids];
    delete $scope.formData.jenis[ids];
    delete $scope.formData.cash_category_id[ids];
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/favorite/'+$stateParams.id+'?_method=PUT&_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('setting.favorite');
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
