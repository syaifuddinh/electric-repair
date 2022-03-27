app.controller('settingReminderType', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Jenis Reminder";
  $scope.data
  // fetch = function() {
  //   $http.get(baseUrl+'/setting/reminder_type').then(function(data) {
  //     $scope.data=data.data;
  //   });
  // }

  // fetch();

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    ordering: false,
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    dom: 'Blfrtip',
    buttons: [{
        extend: 'excel',
        enabled: true,
        action: newExportAction,
        text: '<span class="fa fa-file-excel-o"></span> Export Excel',
        className: 'btn btn-default btn-sm pull-right',
        filename: 'JenisReminder - ' + new Date,
        sheetName: 'Data',
        title: 'Jenis Reminder',
        exportOptions: {
          rows: {
            selected: true
          }
        },
    }],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/setting/reminder_type?dt=true',
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"name",name:"name"},
      {data:"type_name",name:"type_name"},
      {data:"interval",name:"interval"},
      {data:"action",name:"action",className:"text-center", render: function(data, type, row){
        var html = '<a g-show="roleList.includes(\'setting.operational.reminder.edit\')" ng-click="edit('+data.id+')"><i class="fa fa-edit"></i></a>'
        return html
      }},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });
  oTable.buttons().container().appendTo('.ibox-tools')

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/setting/reminder_type/'+ids,{_token:csrfToken}).then(function success(data) {
        // $state.reload();
        $state.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }
  $scope.formData={};
  $scope.url="";
  $scope.types=[
    {id:1,name:"Jam"},
    {id:2,name:"Hari"},
    {id:3,name:"Kilometer"},
  ];
  $scope.edit=function(ids) {
    $scope.modalTitle="Edit Jenis Reminder";
    $http.get(baseUrl+'/setting/reminder_type/'+ids+'/edit').then(function(data) {
      $scope.item=data.data;
      // startdata
      $scope.formData.name=$scope.item.name;
      $scope.formData.type=$scope.item.type;
      $scope.formData.interval=$scope.item.interval;
      // endata
      $('#modal').modal('show');
    });
    $scope.url=baseUrl+'/setting/reminder_type/'+ids+'?_token='+csrfToken;
    $scope.method = 'put';
  }

  $scope.create=function(ids) {
    $scope.modalTitle="Tambah Jenis Reminder";
      // startdata
      $scope.formData.name='';
      $scope.formData.type=2;
      $scope.formData.interval='';
      // endata
      $('#modal').modal('show');
    $scope.url=baseUrl+'/setting/reminder_type';
    $scope.method = 'post';
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http[$scope.method]($scope.url,$scope.formData).then(function(data) {
      $('#modal').modal('hide');
      oTable.ajax.reload()
      toastr.success("Data Berhasil Disimpan!");
      $scope.disBtn=false;
    }, function(error) {
      $scope.disBtn=false;
      if (error.status==422) {
        var det="";
        angular.forEach(error.data.errors,function(val,i) {
          det+="- "+val+"<br>";
        });
        toastr.warning(det,error.data.message);
      } else {
        toastr.error(error.data.message,"Error Has Found !");
      }
    });
  }

});
