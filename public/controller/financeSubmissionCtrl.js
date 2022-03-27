app.controller('financeSubmission', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Pengajuan Biaya";
  $scope.formData = {};

  $http.get(baseUrl+'/finance/submission_cost').then(function(data) {
    $scope.data=data.data
  });

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[[8,'desc']],
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/finance/submission_cost_datatable',
      data : function(d){
        d.company_id = $scope.formData.company_id;
        d.start_submit_date = $scope.formData.start_submit_date;
        d.end_submit_date = $scope.formData.end_submit_date;
        d.start_cost_date = $scope.formData.start_cost_date;
        d.end_cost_date = $scope.formData.end_cost_date;
        d.jenis = $scope.formData.jenis;
        d.status = $scope.formData.status;
      }
    },
    columns:[
      {data:"cname",name:"companies.name",className:"font-bold"},
      {data:"created_at",name:"created_at"},
      {data:"date_submission",name:"date_submission"},
      {data:"type_submission",name:"type_submission"},
      {data:"codes",name:"codes"},
      {data:"description",name:"description"},
      {data:"amount",name:"amount"},
      {data:"status",name:"status"},
      {data:"action",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  // $scope.deletes=function(ids) {
  //   var cfs=confirm("Apakah Anda Yakin?");
  //   if (cfs) {
  //     $http.delete(baseUrl+'/operational/voyage_schedule/'+ids,{_token:csrfToken}).then(function success(data) {
  //       oTable.ajax.reload();
  //       toastr.success("Data Berhasil Dihapus!");
  //     }, function error(data) {
  //       toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
  //     });
  //   }
  // }

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
    var url = baseUrl + '/excel/pengajuan_biaya_export?';
    url += params;
    location.href = url; 
  }
});
app.controller('financeSubmissionShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Pengajuan Biaya";

  $scope.type_submission=[
    {id:1,name:'<span class="badge badge-info">JOB ORDER</span>'},
    {id:2,name:'<span class="badge badge-success">PACKING LIST</span>'},
    {id:3,name:'<span class="badge badge-danger">PICKUP ORDER</span>'},
    {id:4,name:'<span class="badge badge-primary">TRANSAKSI KAS</span>'},
    {id:5,name:'<span class="badge badge-warning">KAS BON</span>'},
  ];
  $scope.status=[
    {id:1,name:'<span class="badge badge-info">Diajukan</span>'},
    {id:2,name:'<span class="badge badge-success">Disetujui</span>'},
    {id:3,name:'<span class="badge badge-danger">Ditolak</span>'},
    {id:4,name:'<span class="badge badge-warning">Diposting</span>'},
    {id:5,name:'<span class="badge badge-warning">Revisi</span>'},
  ];

  $scope.approve=function() {
    var conf=confirm("Apakah anda ingin menyetujui pengajuan biaya ini ?");
    if (conf) {
      $http.post(baseUrl+'/finance/submission_cost/approve/'+$stateParams.id).then(function(data) {
        // $scope.item=data.data
        toastr.success("Pengajuan Biaya Telah Disetujui","Berhasil");
        $state.reload();
      });
    }
  }
  $scope.reject=function() {
    var conf=confirm("Apakah anda ingin menolak pengajuan biaya ini ?");
    if (conf) {
      $http.post(baseUrl+'/finance/submission_cost/reject/'+$stateParams.id).then(function(data) {
        // $scope.item=data.data
        toastr.success("Pengajuan Biaya Telah Ditolak","Berhasil");
        $state.reload();
      });
    }
  }
  $scope.revisi=function() {
    var conf=confirm("Apakah anda ingin merevisi pengajuan biaya ini ?");
    if (conf) {
      $http.post(baseUrl+'/finance/submission_cost/revisi/'+$stateParams.id).then(function(data) {
        // $scope.item=data.data
        toastr.success("Pengajuan Biaya dikembalikan untuk direvisi","Berhasil");
        $state.go('finance.submission_cost');
      });
    }
  }
  $scope.posting=function() {
    var conf=confirm("Apakah anda ingin memposting pengajuan biaya ini ?");
    if (conf) {
      $http.post(baseUrl+'/finance/submission_cost/posting/'+$stateParams.id).then(function(data) {
        // $scope.item=data.data
        toastr.success("Pengajuan Biaya telah Diposting","Berhasil");
        $state.reload();
      });
    }
  }
  $scope.cancel_approve=function() {
    var conf=confirm("Apakah anda ingin membatalkan persetujuan ?");
    if (conf) {
      $http.post(baseUrl+'/finance/submission_cost/cancel_approve/'+$stateParams.id).then(function(data) {
        // $scope.item=data.data
        toastr.success("Pengajuan Biaya telah Dibatalkan","Berhasil");
        $state.go('finance.submission_cost');
      });
    }
  }
  $scope.cancel_posting=function() {
    var conf=confirm("Apakah anda ingin membatalkan posting ?");
    if (conf) {
      $http.post(baseUrl+'/finance/submission_cost/cancel_posting/'+$stateParams.id).then(function(data) {
        // $scope.item=data.data
        toastr.success("Pengajuan Biaya telah Batal Posting","Berhasil");
        // $state.go('finance.submission_cost');
        $state.reload();
      });
    }
  }

  $scope.jenis=[
    {id:1,name:'Masuk'},
    {id:2,name:'Keluar'},
  ]

  $http.get(baseUrl+'/finance/submission_cost/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item
    $scope.cash=data.data.cash
    $scope.cash_detail=data.data.cash_detail
  });
});
