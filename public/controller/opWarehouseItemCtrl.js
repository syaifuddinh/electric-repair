app.controller('opWarehouseItem', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Master Item Warehouse";
    $scope.formData = {};
    $scope.data = {
      status : [
        {id : 1, name : 'Aktif'},
        {id : 0, name : 'Tidak Aktif'}
      ]
    }    
  
    $scope.formData = {};
    oTable = $('#datatable').DataTable({
      processing: true,
      serverSide: true,
      // scrollX:'100%',
      
      dom: 'Blfrtip',
      buttons: [
        {
          'extend' : 'excel',
          'enabled' : true,
          'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
          'className' : 'btn btn-default btn-sm',
          'filename' : 'Master Item - '+new Date(),
          'sheetName' : 'Data',
          'title' : 'Master Item'
        },
      ],
      ajax: {
        headers : {'Authorization' : 'Bearer '+authUser.api_token},
        url : baseUrl+'/api/operational_warehouse/item_datatable',
        data : function(request) {
          request['status'] = $scope.formData.status;
        }
      },
      columns:[
        {data:"code",code:"name"},
        {data:"name",name:"name"},
        {data:"long",name:"long", className : 'text-right'},
        {data:"wide",name:"wide", className : 'text-right'},
        {data:"height",name:"height", className : 'text-right'},
        {data:"tonase",name:"tonase", className : 'text-right'},
        {data:"is_active",name:"is_active", className : 'text-center', orderable : false},
        {data:"action",name:"action", className : 'text-center', orderable : false}
      ],
      createdRow: function(row, data, dataIndex) {
        $compile(angular.element(row).contents())($scope);
      }
    });

    oTable.buttons().container().appendTo( '#export_button' );

    $scope.deletes=function(ids) {
      var cfs=confirm("Apakah Anda Yakin?");
      if (cfs) {
        $http.delete(baseUrl+'/operational_warehouse/item/'+ids,{_token:csrfToken}).then(function success(data) {
          // $state.reload();
          oTable.ajax.reload();
          toastr.success("Data Berhasil Dinon-aktifkan!");
        }, function error(data) {
          toastr.error("Terjadi kesalahan!","Error Has Found!");
        });
      }
    }
  
    $scope.export_excel = function() {
        var request = $.param( $scope.formData );
        var url = baseUrl+'/excel/laporan_penerimaan_barang_export?' + request;
    
        location.href = url;
    }

    $scope.searchData = function() {
        oTable.ajax.reload();
    }

    $scope.resetFilter = function() {
        $scope.formData = {};
        oTable.ajax.reload();
    }
});

app.controller('opWarehouseItemEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Edit Master Item";
    $scope.formData={};
    $('.ibox-content').toggleClass('sk-loading');
  
    $scope.show = function() {

        $http.get(baseUrl+'/operational_warehouse/item/'+$stateParams.id+'/edit').then(function(data) {
            $scope.data=data.data;
            var dt=data.data.item;
            
            $scope.formData.name = dt.name;
            $scope.formData.customer_id = dt.customer_id;
            $scope.formData.barcode = dt.barcode;
            $scope.formData.long = dt.long;
            $scope.formData.wide = dt.wide;
            $scope.formData.height = dt.height;
            $scope.formData.volume = dt.volume;
            $scope.formData.tonase = dt.tonase;
            $('.ibox-content').toggleClass('sk-loading');
        }, function(){
          $scope.show()
        });
    }
    $scope.show()

    $scope.hitung_volume = function() {
        $scope.formData.volume = $scope.formData.long * $scope.formData.wide * $scope.formData.height / 1000000;
    }
  
    $scope.disBtn=false;
    $scope.submitForm=function() {
      $scope.disBtn=true;
      $.ajax({
        type: "post",
        url: baseUrl+'/operational_warehouse/item/'+$stateParams.id+'?_method=PUT&_token='+csrfToken,
        data: $scope.formData,
        success: function(data){
          $scope.$apply(function() {
            $scope.disBtn=false;
          });
          toastr.success("Data Berhasil Disimpan");
          $state.go('operational_warehouse.master_item');
          // oTable.ajax.reload();
        },
        error: function(xhr, response, status) {
          $scope.$apply(function() {
            $scope.disBtn=false;
          });
          // console.log(xhr);
          if (xhr.status==422) {
            var msgs="";
            $.each(xhr.responseJSON.errors, function(i, val) {
              msgs+=val+'<br>';
            });
            toastr.warning(msgs,"Validation Error!");
          } else {
            toastr.error(xhr.responseJSON.message,"Error has Found!");
          }
        }
      });
    }
});

app.controller('opWarehouseItemCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Tambah Master Item";
    $scope.formData={};
    $scope.formData.initial_cost=0;
    $scope.formData.is_stock=0;
    $scope.formData.minimal_stock=0;
    $scope.formData.is_active=1;
    $('.ibox-content').toggleClass('sk-loading');


    $http.get(baseUrl+'/operational_warehouse/item/create').then(function(data) {
      $scope.data=data.data;
      $('.ibox-content').toggleClass('sk-loading');
    });
    $scope.hitung_volume = function() {
        $scope.formData.volume = (parseInt($scope.formData.long) || 0) * (parseInt($scope.formData.wide) || 0) * (parseInt($scope.formData.height) || 0) / 1000000;
    }
  
    $scope.disBtn=false;
    $scope.submitForm=function() {
      $scope.disBtn=true;
      $.ajax({
        type: "post",
        url: baseUrl+'/operational_warehouse/item?_token='+csrfToken,
        data: $scope.formData,
        success: function(data){
          $scope.$apply(function() {
            $scope.disBtn=false;
          });
          toastr.success("Data Berhasil Disimpan");
          $state.go('operational_warehouse.master_item');
        },
        error: function(xhr, response, status) {
          $scope.$apply(function() {
            $scope.disBtn=false;
          });
          // console.log(xhr);
          if (xhr.status==422) {
            var msgs="";
            $.each(xhr.responseJSON.errors, function(i, val) {
              msgs+=val+'<br>';
            });
            toastr.warning(msgs,"Validation Error!");
          } else {
            toastr.error(xhr.responseJSON.message,"Error has Found!");
          }
        }
      });
    } 
});