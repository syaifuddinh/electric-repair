app.controller('operationalJODriver', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Surat Jalan Driver";
  $('.ibox-content').addClass('sk-loading');

  $scope.formData = {};
  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[[7,'desc']],
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational/delivery_order_driver_datatable',
      data : function(request) {
        request['start_date'] = $scope.formData.start_date;
        request['end_date'] = $scope.formData.end_date;
        request['status'] = $scope.formData.status;

        return request;
      },
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"code",name:"code",className:"font-bold"},
      {data:"code_pl",name:"manifests.code"},
      {data:"pick_date",name:"pick_date",className:""},
      {data:"kendaraan",name:"vehicles.nopol"},
      {data:"sopir",name:"driver.name"},
      {data:"trayek",name:"routes.name",className:"font-bold"},
      {data:"status_name",name:"job_statuses.name",className:""},
      {data:"action",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('operational.delivery_order.detail')) {
        $(row).find('td').attr('ui-sref', 'operational.delivery_order_driver.show({id:' + data.id + '})')
        $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
        $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });

  $http.get(baseUrl+'/operational/delivery_order_driver').then(function(data){
    $scope.filterDt=data.data
  },function(error){
    console.log(error)
  })

  $scope.searchData = function() {
    oTable.ajax.reload();
  }
  $scope.resetFilter = function() {
    $scope.formData = {};
    oTable.ajax.reload();
  }

});

app.controller('operationalJODriverShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Detail";
    $scope.id = $stateParams.id;
});
