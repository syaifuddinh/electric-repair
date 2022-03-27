app.controller('inventoryWarehouse', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Warehouse";
  $('.ibox-content').addClass('sk-loading');

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/inventory/warehouse_datatable',
      dataSrc: function (d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"company.name",name:"company.name"},
      {data:"code",name:"code"},
      {data:"name",name:"name",className:"font-bold"},
      {data:"address",name:"address"},
      {data:"capacity_volume",name:"capacity_volume",className:"text-right"},
      {data:"capacity_tonase",name:"capacity_tonase",className:"text-right"},
      {data:null,name:"id",className:"text-center",render:function(e) {
        return `<a ui-sref="inventory.warehouse.show({id:${e.id}})"><span class="fa fa-folder"></span></a>`
      }}
      // {data:"action",name:"action",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });
});
app.controller('inventoryWarehouseShow', function($scope,$http,$rootScope,$state,$stateParams,$filter,$compile) {
  $rootScope.pageTitle="Warehouse Detail";
  $scope.data={}
  $http.get(`${baseUrl}/inventory/warehouse/${$stateParams.id}`).then(function(e) {
    $scope.data = e.data
    oTable.ajax.reload()
  })
  $scope.backward = function() {
    if($rootScope.hasBuffer()) {
        $rootScope.accessBuffer()
    } else {
      $scope.emptyBuffer()
      $state.go('inventory.category')
    }
  }
  oTable = $('#rack_datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational_warehouse/rack_datatable',
      dataSrc: function(d) {
        $('.sk-container').removeClass('sk-loading');
        return d.data;
      },
      data: function(d) {
        d.warehouse_id = $scope.data.id
      }
    },
    columns:[
      {data:"warehouse.company.name",name:"warehouse.company.name"},
      {data:"warehouse.name",name:"warehouse.name"},
      {data:"code",name:"code"},
      {data:"storage_type.name",name:"storage_type.name"},
      {
        data:null,
        orderable:false,
        searchable:false,
        className:'text-right',
        render : resp => $filter('number')(resp.capacity_volume)
      },
      {
        data:null,
        orderable:false,
        searchable:false,
        className:'text-right',
        render : resp => $filter('number')(resp.capacity_volume_used)
      },
      {
        data:null,
        orderable:false,
        searchable:false,
        className:'text-right',
        render : resp => $filter('number')(resp.capacity_tonase)
      },
      {
        data:null,
        orderable:false,
        searchable:false,
        className:'text-right',
        render : resp => $filter('number')(resp.capacity_tonase_used)
      }
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

})
