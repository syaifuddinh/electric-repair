app.controller('operationalShipmentStatus', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
  $rootScope.pageTitle="Shipment Status";
  $scope.isFilter = false;
  $scope.serviceStatus = [];
  $scope.formData = {}
  $scope.checkData = {}
  // $scope.checkData.detail = []
  // $scope.formData.customer_id = null
  // $scope.formData.is_done = 1

   oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    scrollX:false,
    dom: 'Blfrtip',
    initComplete : null,
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational/shipment_status_datatable',
      data : x => Object.assign(x, $scope.formData)
    },
    buttons: [
      {
        'extend' : 'excel',
        'enabled' : true,
        'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
        'className' : 'btn btn-default btn-sm',
        'filename' : 'Job Order - '+new Date(),
        'sheetName' : 'Data',
        'title' : 'Job Order'
      },
    ],

    columns:[
      {data:"code",name:"W.code"},
      {data:"customer_name",name:"C.name"},
      {
        data:null,
        name:"W.receive_date",
        searchable:false,
        render:resp => $filter('fullDate')(resp.receive_date)
      },
      {data:"job_order_code",nullable:false,searchable:false},
      {data:"qty",nullable:false,searchable:false, className:'text-right'},
      {data:"volume",nullable:false,searchable:false, className:'text-right'},
      {
        data:null,
        nullable:false,
        searchable:false, 
        className:'text-right',
        render:resp => $filter('number')(resp.weight)
      },
      {
        data:null,
        nullable:false,
        searchable:false, 
        className:'text-center',
        render: function(resp) {
            var className = ''
            var statusName = ''
            switch (parseInt(resp.status)) {
                case 0:
                  className = 'default'
                  statusName= 'Draft'
                  break;
                case 1:
                  className = 'warning'
                  statusName= 'Diterima'
                  break;                
                case 2:
                  className = 'dark'
                  statusName= 'Terkirim'
                  break;                
                case 3:
                  className = 'primary'
                  statusName= 'Sampai'
                  break;                
                case 4:
                  className = 'success'
                  statusName= 'Selesai'
                  break;                
            }

            return "<span class='label label-" + className + "'>" + statusName + "</span>"
        }
      },
      {
        data:null,
        nullable:false,
        searchable:false,
        render:resp => "<a ui-sref='operational.shipment_status.show({id:" + resp.id + "})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>"
      },

    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });
  oTable.buttons().container().appendTo( '#export_button' );

  $scope.toggleFilter=function()
  {
    $scope.isFilter = !$scope.isFilter
  }
  
  $scope.searchData=function()
  {
    oTable.ajax.reload()
  }
  
  $scope.resetFilter=function()
  {
    $scope.formData = {}
    oTable.ajax.reload()
  }

});


app.controller('operationalShipmentStatusShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
  $rootScope.pageTitle="Detail Shipment Status";
  $scope.formData = {}  

  $scope.showJobOrder = function() {
      $http.get(baseUrl+'/operational_warehouse/receipt/' + $stateParams.id + '/job_order').then(function(data) {
          $scope.formData.job_orders=data.data;
      }, function(){
          $scope.showJobOrder()
      });
  }
  $scope.showJobOrder()

  $scope.showManifest = function() {
      $http.get(baseUrl+'/operational_warehouse/receipt/' + $stateParams.id + '/manifest').then(function(data) {
          $scope.formData.manifests=data.data;
      }, function(){
          $scope.showManifest()
      });
  }
  $scope.showManifest()
});