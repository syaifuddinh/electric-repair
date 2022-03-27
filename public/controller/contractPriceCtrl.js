app.controller('marketingContractPrice', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Contract Price";
  $scope.filterData={}

  $http.get(baseUrl+'/marketing/report/activity_wo_index').then(function(data) {
    $scope.data=data.data;
  });

  $scope.refreshTable=function() {
    oTable.ajax.reload();
  }

  $scope.reset=function() {
    $scope.filterData={}
    oTable.ajax.reload();
  }

  // $scope.exportExcel = function() {
  //   var paramsObj = oTable.ajax.params();
  //   var params = $.param(paramsObj);
  //   var url = baseUrl + '/excel/contract_price_export?';
  //   url += params;
  //   location.href = url; 
  // }

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
        className: 'btn btn-default btn-sm pull-right m-l-sm',
        filename: 'Logistic - Contract Price - ' + new Date,
        sheetName: 'Data',
        title: 'Logistic - Contract Price',
        exportOptions: {
          rows: {
              selected: true
          }
        },
    }],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/marketing/contract_price_datatable',
      data: function(d) {
        d.company_id = $scope.filterData.company_id
        d.customer_id = $scope.filterData.customer_id
        d.start_date = $scope.filterData.start_date
        d.end_date = $scope.filterData.end_date
        d.service_type_id = $scope.filterData.service_type_id
      }
    },
    columns:[
      {data:"company",name:"companies.name"},
      {data:"service",name:"services.name"},
      {data:"service_type",name:"service_types.name"},
      {data:"trayek",name:"routes.name"},
      {data:"customer",name:"contacts.name"},
      {data:"commodity",name:"commodities.name"},
      {data:"vehicle_type",name:"vehicle_type"},
      {data:"imposition_name",name:"imposition_name"},
      {data:"price_contract_full",name:"price_contract_full",className:"text-right"},
      {data:"is_generate",name:"is_generate"},
      {data:"action",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  oTable.buttons().container().appendTo('.ibox-tools')

});

app.controller('marketingContractPriceShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Tarif Kontrak";

  $http.get(baseUrl+'/marketing/inquery/detail_cost/'+$stateParams.id).then(function(res) {
    $scope.data=res.data;
  });

});
