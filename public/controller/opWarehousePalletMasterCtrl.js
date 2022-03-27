app.controller('opWarehousePalletMaster', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle = 'Master Pallet'
    $scope.add = function() {
        $rootScope.insertBuffer()
        $state.go('operational_warehouse.master_pallet.create')
    }
})

app.controller('opWarehousePalletStock', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Stock Pallet";

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[[1,'asc'],[0,'asc']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational_warehouse/pallet_stock_datatable',
    },
    dom: 'Blfrtip',
    buttons: [
      {
        'extend' : 'excel',
        'enabled' : true,
        'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
        'className' : 'btn btn-default btn-sm',
        'filename' : 'Stock Pallet - '+new Date(),
        'sheetName' : 'Data',
        'title' : 'Stock Pallet'
      },
    ],

    columns:[
      {data:"warehouse",name:"warehouses.name"},
      {data:"item_name",name:"items.name"},
      {data:"category",name:"categories.name"},
      {
        data:null,
        render:resp => $filter('number')(resp.qty),
        className:'text-right', 
        orderable:false, 
        searchable:false
      },
      {
        data:null,
        render:resp => $filter('number')(resp.qty_po),
        className:'text-right', 
        orderable:false, 
        searchable:false
      },
      {
        data:null,
        render:resp => $filter('number')(resp.transit),
        className:'text-right', 
        orderable:false, 
        searchable:false
      },
      // {data:"action",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });
  oTable.buttons().container().appendTo( '#export_button' );

})
