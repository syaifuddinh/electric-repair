app.controller('opWarehousePackaging', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter, packagingsService) {
    $rootScope.source = null
    $rootScope.pageTitle="Packaging";
    $scope.isFilter = false;
    $scope.serviceStatus = [];
    $scope.formData = {}
    $scope.checkData = {}

    $scope.disableArchive=true
    $scope.isCheck=function() {
    $scope.disableArchive=true
        angular.forEach($scope.checkData.detail, function(val,i) {
            if (val.value) {
                return $scope.disableArchive=false;
            }
        })
    }

  oTable = $('#datatable').DataTable({
    processing: true,
    order:[[2, 'desc']],
    serverSide: true,
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational_warehouse/packaging_datatable',
      data : e => Object.assign(e, $scope.formData)
    },

    dom: 'Blfrtip',
    buttons: [{
      extend: 'excel',
      enabled: true,
      action: newExportAction,
      text: '<span class="fa fa-file-excel-o"></span> Export Excel',
      className: 'btn btn-default btn-sm pull-right',
      filename: 'Packaging',
      sheetName: 'Data',
      title: 'Packaging',
      exportOptions: {
        rows: {
          selected: true
        }
      },
    }],
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    columns:[
      {data:"code",name:"packagings.code"},
      {data:"company_name",name:"companies.name"},
      {data:"warehouse_name",name:"warehouses.name"},
      {data:"date",name:"packagings.date"},
      {data:"description",name:"packagings.description"},
      {
            data:null,
            render : function(resp) {
                var r = ''; 
                r += '<a ng-show="roleList.includes(\'inventory.packaging.detail\')" ui-sref="operational_warehouse.packaging.show({id:' + resp.id + '})"><i class="fa fa-folder-o"></i></a>'
                r += '&nbsp;&nbsp;<a ng-show="roleList.includes(\'inventory.packaging.edit\')" ng-click="edit(' + resp.id + ')"><i class="fa fa-edit"></i></a>'
                r += '&nbsp;&nbsp;<a ng-show="roleList.includes(\'inventory.packaging.delete\')" ng-click="delete(' + resp.id + ')"><i class="fa fa-trash-o"></i></a>'

                return r;
            },
            searchable:false,
            orderable:false,
            className:"text-center"
      },
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });
  oTable.buttons().container().appendTo('.ibox-tools')
  $compile($('thead'))($scope);

  $scope.toggleFilter=function()
  {
    $scope.isFilter = !$scope.isFilter
  }

    $scope.refresh = function()
    {
        oTable.ajax.reload()
    }


    $scope.resetFilter=function()
    {
        $scope.formData = {}
        $scope.refresh()
    }

    $scope.delete = function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            packagingsService.api.destroy(ids, function(){
                oTable.ajax.reload();
            })
        }
    }

    $scope.edit = function(ids) {
        $rootScope.insertBuffer()
        $state.go('operational_warehouse.packaging.edit', {'id' : ids})
    }
    $rootScope.insertBuffer()
});

app.controller('opWarehousePackagingCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter, packagingsService) {
    $scope.formData = {}
    $scope.formData.date = dateNow;
    $scope.formData.old_items = []
    $scope.formData.new_items = []

    $scope.showNewItem = function() {
        if($stateParams.id) {
            packagingsService.api.showNewItem($stateParams.id, function(dt){
                $scope.formData.new_items = dt
            })
        }
    }

    $scope.showOldItem = function() {
        if($stateParams.id) {
            packagingsService.api.showOldItem($stateParams.id, function(dt){
                $scope.formData.old_items = dt
            })
        }
    }

    $scope.show = function() {
        if($stateParams.id) {
            packagingsService.api.show($stateParams.id, function(dt){
                $scope.formData = dt
                $scope.showOldItem()
                $scope.showNewItem()
            })
        }
    }
    $scope.show()
    
    $scope.showItems = function() {
        $scope.$broadcast('showItemsModal', 0)
    }

    $scope.showMasterItems = function() {
        $scope.$broadcast('showItemsModal', 0)
    }

    $scope.appendNewItem = function(v) {
        var id = Math.round(Math.random() * 9999999999)
        v.item_id = v.id
        v.id = id
        v.item_name = v.name
        $scope.formData.new_items.push(v)
    }

    $scope.appendOldItem = function(v) {
        var id = Math.round(Math.random() * 9999999999)
        v.item_id = v.id
        v.id = id
        v.item_name = v.name
        $scope.formData.old_items.push(v)
    }

    $scope.deleteNewItem = function(id) {
        var detail = $scope.formData.new_items.filter(x => x.id != id)
        $scope.formData.new_items = detail
    }

    $scope.deleteOldItem = function(id) {
        console.log($scope.formData.old_items)
        var detail = $scope.formData.old_items.filter(x => x.id != id)
        console.log(detail)
        $scope.formData.old_items = detail
    }

    $scope.$on('getItem', function(e, v){
        $scope.appendNewItem(v)
    })

    $scope.$on('getItems', function(e, items){
        for(i in items) {
            $scope.appendNewItem(items[i])
        }
    })

    $scope.$on('getItemWarehouse', function(e, v){
        $scope.appendOldItem(v)
    })

    $scope.$on('getItemWarehouses', function(e, items){
        var i
        for(i in items) {
            $scope.appendOldItem(items[i])
        }
    })

    $scope.back = function() {
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
            $rootScope.emptyBuffer()
            $state.go('operational_warehouse.packaging')
        }
    }

    $scope.submitForm=function() {
        $rootScope.disBtn = true;
        if($stateParams.id) {
            packagingsService.api.update($scope.formData, $stateParams.id, function(){
                $scope.back()
            })
        } else {
            packagingsService.api.store($scope.formData, function(){
                $scope.back()
            })
        }
    }
});

app.controller('opWarehousePackagingShow', function($scope,  $http,  $rootScope, $state, $stateParams, $timeout, $compile, $filter, packagingsService) {
    $rootScope.pageTitle = $rootScope.solog.label.general.detail

    $scope.approve=function() {
        is_confirm = confirm('Are you sure ?')
        if(is_confirm) {
            $rootScope.disBtn = true;
            if($stateParams.id) {
                packagingsService.api.approve($stateParams.id, function(){
                    $scope.show()
                })
            }
        }
    }

    $scope.showNewItem = function() {
        if($stateParams.id) {
            packagingsService.api.showNewItem($stateParams.id, function(dt){
                $scope.formData.new_items = dt
            })
        }
    }

    $scope.showOldItem = function() {
        if($stateParams.id) {
            packagingsService.api.showOldItem($stateParams.id, function(dt){
                $scope.formData.old_items = dt
            })
        }
    }

    $scope.show = function() {
        if($stateParams.id) {
            packagingsService.api.show($stateParams.id, function(dt){
                $scope.formData = dt
                $scope.showOldItem()
                $scope.showNewItem()
            })
        }
    }
    $scope.show()
});

