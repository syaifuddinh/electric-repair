app.controller('Klaim', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Klaim";
});

app.controller('KlaimCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Tambah";
  $scope.formData={};
  $scope.detail={};
  $scope.params={};
  $scope.formData.detail=[];
  $scope.formData.company_id=compId;
  $scope.formData.date_transaction=dateNow;
  $scope.formData.pengenaan=1;
  $scope.formData.job_order_id=0;
    $scope.pengenaan=[
    {id:1,name:"Vendor"},
    {id:2,name:"Karyawan/Driver"},
    {id:3,name:"Perusahaan Sendiri"},
  ];

  $scope.detail.type=1;
  $scope.detail.qty=0;
  $scope.detail.harga=0;
  $scope.detail.jumlah=0;
  $scope.detail.qty2=0;
  $scope.detail.harga2=0;
  $scope.detail.jumlah2=0;
  $scope.formData.total=0;


  $scope.cariJO=function(id) {
    if (id) {
      $scope.params.customer_id=id;
      jo_datatable.ajax.reload(function() {
        $('#modalJO').modal('show');
      });
    } else {
      toastr.error("Customer Belum Dipilih!","Maaf!");
    }
  }

  $scope.selectJO=function(id,code) {
    $scope.formData.job_order_id=id;
    $scope.formData.job_order_code=code;
    $('#modalJO').modal('hide');
  }

    var jo_datatable = $('#jo_datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[[0,'desc']],
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational/job_order_datatable',
      data: function(d) {
        d.collectible_id=$scope.params.customer_id;
        d.service_not_in=[4];
        d.not_invoice=true;
      }
    },
    columns:[
      {data:"action_choose",name:"created_at",className:"text-center",orderable:false},
      {data:"code",name:"code"},
      {data:"shipment_date",name:"shipment_date"},
      {data:"no_po_customer",name:"no_po_customer",className:"font-bold"},
      {data:"service",name:"services.name"},
      {data:"service_type",name:"service_types.name",className:""},
      {data:"trayek",name:"routes.name"},
      {data:"customer",name:"contacts.name",className:"font-bold"},
      {data:"receiver_name",name:"receiver.name",className:""},
      {data:"sender_name",name:"sender.name",className:""},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });


    $scope.cariKomoditas=function(id) {
    if (id) {
      komoditas_datatable.ajax.reload(function() {
        $('#modalKomoditas').modal('show');
      });
    } else {
      toastr.error("Job Order Belum Dipilih!","Maaf!");
    }
  }


    var urut=0
    $scope.appendTable=function() {
    var dt=$scope.detail;
    var reff="";
    if ($scope.detail.type==2) {
      reff+=$('#KomoditasLain option:selected').text();
    } else {
      reff+=$scope.detail.komoditasJO;
    }
    var html="";
    html+="<tr id='row-"+urut+"'>";
    html+="<td>"+reff+"</td>";
    html+="<td>"+$filter('number')($scope.detail.qty)+"</td>";
    html+="<td>"+$filter('number')($scope.detail.harga)+"</td>";
    html+="<td>"+$filter('number')($scope.detail.jumlah)+"</td>";
    html+="<td>"+$filter('number')($scope.detail.jumlah2)+"</td>";
    html+="<td>"+$('#desc').val()+"</td>";
    html+="<td><a ng-click='deleteAppend("+urut+")'><span class='fa fa-trash'></span></a></td>";
    html+="</tr>";

    $('#appendTable tbody').append($compile(html)($scope));
    $scope.formData.detail.push($scope.detail);

    $scope.detail={};
    $scope.detail.type=1;
    $scope.detail.qty=0;
    $scope.detail.harga=0;
    $scope.detail.jumlah=0;
    $scope.detail.qty2=0;
    $scope.detail.harga2=0;
    $scope.detail.jumlah2=0;
    $scope.detail.description='';
    urut++;

    $scope.hitungTotalBayar();
  }

  $scope.hitungTotalBayar=function() {
    var total=0;
    angular.forEach($scope.formData.detail,function(val,i) {
      total+=parseFloat(val.jumlah);
    });
    $scope.formData.total=total;
  }

  $scope.deleteAppend=function(ids) {
    $('#row-'+ids).remove();
    delete $scope.formData.detail[ids];
    $scope.hitungTotalBayar();
  }


});

app.controller('KlaimShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Klaim";

});
