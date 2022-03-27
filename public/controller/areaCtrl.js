app.controller('setting', function($scope, $http, $rootScope) {
  $rootScope.pageTitle='Setting';
  $scope.$state=$state;
});
app.controller('settingAreaIndex', function($scope, $http, $rootScope,$state,$timeout,$compile) {
    $rootScope.pageTitle='Area';
    $('.ibox-content').addClass('sk-loading');

  $scope.addArea=function() {
    $scope.modalAreaTitle='Add Area';
    $scope.urlArea=baseUrl+'/setting/area';
    $scope.methodArea='post';
    $scope.areaName='';
    $('#modalArea').modal('show');
  }

  $scope.editArea=function(ids) {
    $http.get(baseUrl+'/setting/area/'+ids+'/edit').then(function success(data) {
      
      $scope.modalAreaTitle='Edit Area';
      $scope.urlArea=baseUrl+'/setting/area/'+data.data.id;
      $scope.methodArea='PUT';
      $scope.areaName=data.data.name;
      $('#modalArea').modal('show');
    });
  }

  dt = 0

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order: [],
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    dom: 'Blfrtip',
    buttons: [{
      extend: 'excel',
      enabled: true,
      action: newExportAction,
      text: '<span class="fa fa-file-excel-o"></span> Export Excel',
      className: 'btn btn-default btn-sm pull-right',
      filename: 'Area',
      sheetName: 'Data',
      title: 'Area',
      exportOptions: {
        rows: {
          selected: true
        }
      },
    }],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/setting/area/area_datatable',
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {name:"name",data:"name"},
      {
        searchable:false,
        orderable:false,
        data:"action",
        className:"text-center"
      }
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('marketing.price.price_list.detail')) {
          $(row).find('td').attr('ng-click', 'editArea(' + data.id + ')')
          $(row).find('td:last-child').removeAttr('ng-click')
      } else {
          $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }

  });

  oTable.buttons().container().appendTo('.ibox-tools')

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/setting/area/'+ids,{_token:csrfToken}).then(function(res) {
        toastr.success("Data Berhasil Dihapus!");
        oTable.ajax.reload();
      }, function(res) {
        toastr.error("Data Tidak dapat Dihapus!");
      })
    }
  }

  $('.formz').validate({
  submitHandler: function(form){
    $.ajax({
      type: $scope.methodArea,
      url: $scope.urlArea,
      data: $(form).serialize()+'&_token='+csrfToken,
      dataType: "json",
      success: function(data){
        $('#modalArea').modal('hide');
        oTable.ajax.reload();
      },
      error: function(xhr,response,error){
      	alert(xhr.responseText);
      }
    });
  	}
  });

});
