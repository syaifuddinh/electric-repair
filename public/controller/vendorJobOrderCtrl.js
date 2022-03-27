app.controller('vendorJobOrder', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Job Order Vendor";
  $scope.formData = {};

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[[0,'desc']],
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    dom:"Blfrtip",
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational/job_order_cost_datatable',
      data: function(d) {
        d.status = $scope.formData.status;
        d.start_date = $scope.formData.start_date;
        d.end_date = $scope.formData.end_date;

        return d;
      }
    },
    buttons:[
      {
        'extend' : 'excel',
        'action' : newExportAction,
        'enabled' : true,
        'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
        'className' : 'btn btn-default btn-sm',
        'filename' : 'Biaya Job Order',
      },
    ],
    columns:[
      {data:"created_at",name:"created_at"},
      {data:"code",name:"job_orders.code",className:"font-bold"},
      {data:"vendor",name:"contacts.name"},
      {data:"total_price",name:"total_price"},
      {data:"cost_type",name:"cost_types.name"},
      {data:"description",name:"description"},
      {data:"status",name:"status"},
      {data:"action_vendor",name:"action_vendor",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });
  oTable.buttons().container().appendTo( '#export_button' );

  $scope.searchData = function() {
    oTable.ajax.reload();
  }
  $scope.resetFilter = function() {
    $scope.formData = {};
    oTable.ajax.reload();
  }

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/operational/voyage_schedule/'+ids,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

  $scope.status=[
    {id:1,name:'Belum Diajukan'},
    {id:2,name:'Diajukan Keuangan'},
    {id:3,name:'Disetujui Keuangan'},
    {id:4,name:'Ditolak'},
    {id:5,name:'Diposting'},
    {id:6,name:'Revisi'},
    {id:7,name:'Diajukan'},
    {id:8,name:'Disetujui Atasan'},
  ]

});
