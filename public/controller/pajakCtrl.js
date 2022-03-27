app.controller('pajak', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Faktur Pajak";
  $scope.formData = {}


  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/finance/pajak_datatable',
      data : function(d){
      }
    },
    columns:[
      {data:"code",name:"code",className:"font-bold"},
      {
        data:'invoice_code',
        name:"invoices.code",
      },
      {
        data:null,
        searchable:false,
        name:"expiry_date",
        render: resp => $filter('fullDate')(resp.start_date)
      },
      {
        data:null,
        searchable:false,
        name:"expiry_date",
        render: resp => $filter('fullDate')(resp.expiry_date)
      },
      
      {
        data:null,
        name:"is_active",
        searchable:false,
        className : 'text-center',
        render : function(resp) {
          var label = resp.is_active == 0 ? 'Tidak Aktif' : 'Aktif';
          var className = resp.is_active == 0 ? 'label-danger' : 'label-primary';
          var outp = '<span class="label ' + className + '">' + label + '</span>';

          return outp;
        }
      }
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.showGenerate = function () {
    $('#modalGenerate').modal();
  }
  $scope.submitPajak = function () {
    var cofs = confirm("Apakah anda yakin ?");
    if (!cofs) {
      return null;
    }
    $scope.disBtn = true
    $http.post(baseUrl + '/finance/pajak', $scope.formData).then(function (data) {
      $scope.disBtn = false
      toastr.success("Faktur Pajak Berhasil Di-generate!");
      oTable.ajax.reload();
      $scope.formData = {}
      $('#modalGenerate').modal('hide')
    }, function(error){
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
