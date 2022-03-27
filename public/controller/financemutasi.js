app.controller('PermintaanMutasi', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
  $rootScope.pageTitle="Permintaan Mutasi";
  $http.get(baseUrl+'/finance/cash_migration').then(function(data) {
    $scope.data=data.data
  });  
  $scope.formData = {};
  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order: [[8,'desc']],
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    dom: 'Blfrtip',
    buttons: [{
        extend: 'excel',
        enabled: true,
        action: newExportAction,
        text: '<span class="fa fa-file-excel-o"></span> Export Excel',
        className: 'btn btn-default btn-sm pull-right',
        filename: 'Permintaan Mutasi',
        sheetName: 'Data',
        title: 'Permintaan Mutasi',
        exportOptions: {
          rows: {
            selected: true
          }
        },
    }],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/finance/cash_migration_datatable',
      data : function(request) {
        request.source_company_id = $scope.formData.source_company_id;
        request.dest_company_id = $scope.formData.dest_company_id;
        request.start_date = $scope.formData.start_date;
        request.end_date = $scope.formData.end_date;
        request.status = $scope.formData.status;
        request.statusIn = "1,2,3,5";
      }
    },
    columns:[
      {data:"code",name:"code",className:"font-bold"},
      {
        data:null,
        orderable:false,
        searchable:false,
        render: resp => $filter('fullDate')(resp.date_request)
      },
      // {data:"date_needed",name:"date_needed",className:""},
      {data:"company_from",name:"company_from.name"},
      {data:"company_to",name:"company_to.name"},
      {data:"account_from",name:"account_from.name"},
      {data:"account_to",name:"account_to.name"},
      {data:"total",name:"total",className:"text-right"},
      {
        data:"status_label",
        orderable:false,
        searchable:false,
        className:"text-center"
      },
      {
        data:null,
        searchable:false,
        name:"created_at",
        className:"text-center",
        render : function(item) {
            var html = ''
            html += "<a ng-show=\"roleList.includes('finance.mutasi_kas.request.detail')\" ui-sref=\"finance.permintaan_mutasi.show({id:" + item.id + "})\" data-toggle='tooltip' title='Show Detail'><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            if (item.status==1) {
                html += "<a ng-show=\"roleList.includes('finance.mutasi_kas.request.edit')\" ui-sref=\"finance.permintaan_mutasi.edit({id:" + item.id + "})\" data-toggle='tooltip' title='Edit Data'><span class='fa fa-edit'></span></a>&nbsp;&nbsp;";
                html += "<a ng-show=\"roleList.includes('finance.mutasi_kas.request.delete')\" ng-click=\"delete(" + item.id + ")\" data-toggle='tooltip' title='Hapus Data'><span class='fa fa-trash-o'></span></a>";
            }
            return html;
        }
      },
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('finance.mutasi_kas.request.detail')) {
          $(row).find('td').attr('ui-sref', 'finance.permintaan_mutasi.show({id:' + data.id + '})')
          $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
          $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });
  oTable.buttons().container().appendTo('.ibox-tools')

  $scope.delete=function(id) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/finance/cash_migration/' + id, {_token:csrfToken}).then(function success(data) {
        // $state.reload();
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }
  
  $scope.searchData = function() {
    oTable.ajax.reload();
  }

  $scope.resetFilter = function() {
    $scope.formData = {};
    oTable.ajax.reload();
  }
});

app.controller('PermintaanMutasiCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah";
  $scope.formData={}
  $scope.formData.date_request=dateNow
  $scope.formData.date_needed=dateNow
  $scope.formData.total=0
  $('.ibox-content').toggleClass('sk-loading');

  $http.get(baseUrl+'/finance/cash_migration/create').then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').toggleClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/finance/cash_migration',$scope.formData).then(function(data) {
      $state.go('finance.permintaan_mutasi');
      toastr.success("Data Berhasil Disimpan!");
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

});
app.controller('PermintaanMutasiEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Tambah";
  $scope.formData={}
  $('.ibox-content').toggleClass('sk-loading');

  $http.get(baseUrl+'/finance/cash_migration/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data;
    var d=$scope.data.item;
    $scope.formData.date_request=$filter('minDate')(d.date_request)
    $scope.formData.date_needed=$filter('minDate')(d.date_needed)
    $scope.formData.company_from=d.company_from
    $scope.formData.company_to=d.company_to
    $scope.formData.cash_account_from=d.cash_account_from
    $scope.formData.cash_account_to=d.cash_account_to
    $scope.formData.total=d.total
    $scope.formData.description=d.description
    $('.ibox-content').toggleClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.put(baseUrl+'/finance/cash_migration/'+$stateParams.id,$scope.formData).then(function(data) {
      $state.go('finance.permintaan_mutasi');
      toastr.success("Data Berhasil Disimpan!");
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

});

app.controller('PermintaanMutasiShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Permintaan Mutasi";
  $('.ibox-content').toggleClass('sk-loading');

  $scope.status=[
    {id:1,name:"Pengajuan"},
    {id:2,name:"Persetujuan Keuangan"},
    {id:3,name:"Persetujuan Direksi"},
    {id:4,name:"Realisasi"},
  ]

  $http.get(baseUrl+'/finance/cash_migration/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $scope.data=data.data;
    $('.ibox-content').toggleClass('sk-loading');
  });

  
    $scope.show_reject = function() {
        $('#modalReject').modal('show');
    }

    $scope.reject = function() {
        $http
            .post(baseUrl+'/finance/cash_migration/reject/'+$stateParams.id, $scope.formData)
            .then(
                function(data) {
                    toastr.success("Mutasi Kas telah Ditolak!");
                    $('#modalReject').modal('hide');
                    $state.reload();
                });
    }


  $scope.approve=function() {
    var cofs=confirm("Apakah anda yakin ?");
    if (!cofs) {
      return null;
    }
    $http.post(baseUrl+'/finance/cash_migration/approve/'+$stateParams.id).then(function(data) {
      toastr.success("Mutasi Kas telah Disetujui Keuangan!");
      $state.reload();
    });
  }
  $scope.approveDireksi=function() {
    var cofs=confirm("Apakah anda yakin ?");
    if (!cofs) {
      return null;
    }
    $http.post(baseUrl+'/finance/cash_migration/approve_direction/'+$stateParams.id).then(function(data) {
      toastr.success("Mutasi Kas telah Disetujui Direksi!");
      $state.reload();
    });
  }
  $scope.realisation=function() {
    var cofs=confirm("Apakah anda yakin ?");
    if (!cofs) {
      return null;
    }
    $http.post(baseUrl+'/finance/cash_migration/realisation/'+$stateParams.id).then(function(data) {
      toastr.success("Mutasi Kas telah Direalisasi!");
      $state.reload();
    },function(error) {
      return toastr.error("Maaf!",error.data.message,"error")
    });
  }
});

app.controller('RealisasiMutasi', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Realisasi Mutasi";
  $scope.formData = {};

  $http.get(baseUrl+'/finance/cash_migration').then(function(data) {
    $scope.data=data.data
  });

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/finance/cash_migration_datatable',
      data: function(d){
        d.status = 4;
        d.source_company_id = $scope.formData.source_company_id;
        d.dest_company_id = $scope.formData.dest_company_id;
        d.start_date = $scope.formData.start_date;
        d.end_date = $scope.formData.end_date;
      }
    },
    dom: 'Blfrtip',
    buttons: [{
        extend: 'excel',
        enabled: true,
        action: newExportAction,
        text: '<span class="fa fa-file-excel-o"></span> Export Excel',
        className: 'btn btn-default btn-sm pull-right',
        filename: 'Realisasi Mutasi',
        sheetName: 'Data',
        title: 'Realisasi Mutasi',
        exportOptions: {
          rows: {
            selected: true
          }
        },
    }],
    columns:[
      {data:"code",name:"code",className:"font-bold"},
      {data:"date_request",name:"date_request",className:""},
      // {data:"date_needed",name:"date_needed",className:""},
      {data:"company_from",name:"company_from.name"},
      {data:"company_to",name:"company_to.name"},
      {data:"account_from",name:"account_from.name"},
      {data:"account_to",name:"account_to.name"},
      {data:"total",name:"total",className:"text-right"},
      {data:"status",name:"status",className:"text-center"},
      {data:"action_realisation",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('finance.mutasi_kas.request.detail')) {
          $(row).find('td').attr('ui-sref', 'finance.realisasi_mutasi.show({id:' + data.id + '})')
          $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
          $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });
  oTable.buttons().container().appendTo( '.ibox-tools')

  $scope.searchData = function() {
    oTable.ajax.reload();
  }

  $scope.resetFilter = function() {
    $scope.formData = {};
    oTable.ajax.reload();
  }

  $scope.exportExcel = function() {
    var paramsObj = oTable.ajax.params();
    var params = $.param(paramsObj);
    var url = baseUrl + '/excel/realisasi_mutasi_export?';
    url += params;
    location.href = url; 
  }
});

app.controller('RealisasiMutasiCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah";
  $('.ibox-content').toggleClass('sk-loading');

  $http.get(baseUrl+'/setting/tax/create').then(function(data) {
    
    $scope.data=data.data;
    $('.ibox-content').toggleClass('sk-loading');
  });

});

app.controller('RealisasiMutasiShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Realisasi Mutasi";
  $('.ibox-content').toggleClass('sk-loading');

  $scope.status=[
    {id:1,name:"Pengajuan"},
    {id:2,name:"Persetujuan Keuangan"},
    {id:3,name:"Persetujuan Direksi"},
    {id:4,name:"Realisasi"},
  ]

  $http.get(baseUrl+'/finance/cash_migration/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $scope.data=data.data;
    $('.ibox-content').toggleClass('sk-loading');
  });

});
