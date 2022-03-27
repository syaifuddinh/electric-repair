app.controller('settingWidget', function($scope, $http, $rootScope,$state,$timeout,$compile) {
    $scope.data = {}
    
    oTable = $('#datatable').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        headers : {'Authorization' : 'Bearer '+authUser.api_token},
        url : baseUrl+'/api/setting/widget_datatable',
      },
      columns:[
        {data:"name",name:"name"},
        {data:"query_name",name:"Q.name"},
        {data:"type",name:"type", className:'capitalize'},
        {data:"width",name:"width", className:'text-right'},
        {
          data:null,
          searchable:false,
          orderable:false,
          className:'text-center',
          render : resp =>  
          "<a ng-disabled='disBtn' ng-click='delete($event.currentTarget)' title='Hapus'><i class='fa fa-trash'></i></a>&nbsp;&nbsp" +  
          "<a  ui-sref='setting.widget.edit({id:" + resp.id + "})' title='Edit'><i class='fa fa-pencil'></i></a>&nbsp;&nbsp"
        },
      ],
      createdRow: function(row, data, dataIndex) {
        $compile(angular.element(row).contents())($scope);
      }
    });


    $scope.delete = function(obj) {
        is_confirm = confirm("Apakah anda yakin ingin menghapus data ini ?")
        if(is_confirm) {
            var row = $(obj).parents('tr')
            var data = oTable.row(row).data()
            $rootScope.disBtn = true
            $http.delete(baseUrl+'/setting/widget/' + data.id).then(function success(data) {
              toastr.success('Data berhasil dihapus')
              oTable.ajax.reload()
            }, function error(data) {
              $rootScope.disBtn = false
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
    }

});
