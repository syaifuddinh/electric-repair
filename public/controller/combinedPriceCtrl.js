app.controller('combinedPrice', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Paket";
  $('.ibox-content').addClass('sk-loading');
  $scope.formData = {}
  $scope.isFilter = false;

  $scope.getData = function() {

      $http.get(baseUrl+'/marketing/combined_price').then(function(data) {
        $scope.data=data.data;
        $scope.services = $scope.data.service
      }, function(){
          $scope.getData()
      });
  }
  $scope.getData()

  $scope.reset_filter = function() {
    $scope.formData = {};
    $scope.refresh_table();
  }

  $scope.refresh_table=function() {
    oTable.ajax.reload();
  }
  
  $scope.export_excel = function() {
    var paramsObj = oTable.ajax.params();
    var params = $.param(paramsObj);
    var url = baseUrl + '/excel/combined_price_export?';
    url += params;
    location.href = url; 
  }

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/marketing/combined_price_datatable',
      data : function(d) {
        d.company_id = $scope.formData.company_id;
        d.is_active = $scope.formData.is_active;
      },
      dataSrc: function(d) {
          $('.ibox-content').removeClass('sk-loading');
          return d.data;
      }
    },
    columns:[
      {data:"company.name",name:"company.name"},
      {data:"code",name:"code"},
      {data:"name",name:"name"},
      {data:"total_item",name:"total_item", className:'text-right'},
      {
        data:null,
        name:"status", 
        className:"text-center",
        orderable : false,
        render : resp => resp.is_active == 0 ? '<span class="badge badge-danger">' + resp.status + '</span>' : '<span class="badge badge-success">' + resp.status + '</span>'
      },
      {data:"action",name:"action", className:"text-center", orderable:false}
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/marketing/combined_price/'+ids,{_token:csrfToken}).then(function success(data) {
        // $state.reload();
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Error Has Found!");
      });
    }
  }

  $scope.activate=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.put(baseUrl+'/marketing/combined_price/activate/'+ids,{_token:csrfToken}).then(function success(data) {
        // $state.reload();
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Error Has Found!");
      });
    }
  }

});
app.controller('combinedPriceCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah Paket";
  $scope.formData={ detail : []};
  $scope.detailData={};

  $scope.create = function() {

      $http.get(baseUrl+'/marketing/combined_price/create').then(function(data) {
        $scope.data=data.data
        $scope.services=data.data.service
        $scope.services = $scope.services.filter(value => true)
        $scope.show()
      }, function() {
          $scope.create()
      });
  }
  $scope.create()

  $scope.appendTable=function() {
    
    $scope.formData.detail.push({
      service_id:$scope.detailData.service.id,
    })

    var html = '';
    html+="<tr>"
    html+="<td>"+$scope.detailData.service.name+"</td>"
    html+="<td class='text-center'><a ng-click='deleteAppend("+$scope.detailData.service.id+")'><span class='fa fa-trash'></span></a></td>"
    html+="</tr>"

    $('#appendTable').append($compile(html)($scope));
    $scope.services = $scope.services.filter(value => value.id != $scope.detailData.service.id) 
    $scope.detailData = {};
  }
  
  $scope.deleteAppend = function(service_id) {
      var index = $scope.formData.detail.findIndex(value => value.service_id == service_id) + 1;
      $scope.formData.detail = $scope.formData.detail.filter(value => value.service_id != service_id);
      var service = $scope.data.service.find(value => value.id == service_id);
      $scope.services.push(service);
      $('tbody tr:eq(' + index + ')').remove();
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    var type = $scope.is_edit == 1 ? 'PUT' : 'POST';
    var url = $scope.is_edit == 1 ? '/marketing/combined_price/' + $stateParams.id + '?_token='+csrfToken : '/marketing/combined_price?_token='+csrfToken;
    $.ajax({
      'type': type,
      'url': url,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('marketing.combined_price');
      },
      error: function(xhr, response, status) {
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        // console.log(xhr);
        if (xhr.status==422) {
          var msgs="";
          $.each(xhr.responseJSON.errors, function(i, val) {
            msgs+='- '+val+'<br>';
          });
          toastr.warning(msgs,"Validation Error!");
        } else {
          toastr.error(xhr.responseJSON.message,"Error has Found!");
        }
      }
    });
  }

  // Jika berada dalam form edit
  $scope.show = function() {
      if($stateParams.id) {
        $scope.is_edit = 1;
          $http.get(baseUrl+'/marketing/combined_price/'+$stateParams.id).then(function(data) {
                $scope.formData=data.data.item;
                $scope.formData.detail=[];

                angular.forEach(data.data.detail, function(value){
                    $scope.detailData.service = value.service;
                    $scope.appendTable();
                })
          }, function(){
              $scope.show()
          });
      }
  }
  // =============================

});

app.controller('combinedPriceShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Paket";
  $('.ibox-content').addClass('sk-loading');

  $scope.data_id = $stateParams.id;

  $http.get(baseUrl+'/marketing/combined_price/'+$stateParams.id).then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').removeClass('sk-loading');
  });
});
