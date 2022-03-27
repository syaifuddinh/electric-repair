app.controller('opWarehouseStockList', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle = $rootScope.solog.label.general.stocklist;
});

app.controller('opWarehouseStockListShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Stok Gudang";

  $scope.formData = {};

  $scope.get = function() {
      $http.get(baseUrl+'/operational_warehouse/stocklist/' + $stateParams.id).then(function(data) {
          $scope.item=data.data;
      }, function(){
          $scope.get()
      });
  }
  $scope.get()

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    scrollX: false,
    order: [[ 5, "desc" ]],
    'initComplete' : function() {
        unitTable = this.api();
        setTimeout(function(){
            unitTable.order([5, 'DESC']).draw();
        }, 600);
    },
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational_warehouse/stocklist_datatable',
      data : function(d) {
        d.customer_id = $scope.formData.customer_id;
        d.warehouse_id = $scope.formData.warehouse_id;
        d.start_date = $scope.formData.start_date;
        d.end_date = $scope.formData.end_date;
        d.start_qty = $scope.formData.start_qty;
        d.end_qty = $scope.formData.end_qty;

        return d;
      }
    },
    columns:[
      {data:"no_surat_jalan",name:"warehouse_stock_details.no_surat_jalan"},
      {data:"customer_name",name:"contacts.name"},
      {data:"sender",name:"sender"},
      {data:"receiver",name:"receiver"},
      {data:"name",name:"items.name"},
      {data:"warehouse_name",name:"warehouses.name"},
      {
        data:null,
        orderable:false,
        searchable:false,
        render:function(resp) {
          var date = resp.receive_date.split(' ');
          return $filter('fullDate')(date[0]) + ' ' + date[1]
        }
      },
      {data:"qty",name:"warehouse_stock_details.qty", className : 'text-right'},
      // {data:"action",name:"action",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $(row).find('td').attr('ng-click', 'detail($event.currentTarget)');
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.detail = function(e) {
      var tr = $(e).parents('tr')
      var data = oTable.row(tr).data();
      $state.go('operational_warehouse.stocklist.show', {id : data.id});
  }

   $scope.searchData = function() {
    oTable.ajax.reload();
  }
  $scope.resetFilter = function() {
    $scope.formData = {};
    oTable.ajax.reload();
  }
});
