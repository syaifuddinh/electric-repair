app.controller('NotaHutang', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Nota Hutang";
});

app.controller('NotaHutangCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Tambah";
  $scope.formData={};
  $scope.detail={};
  $scope.params={};
  $scope.formData.detail=[];
  $scope.formData.company_id=compId;
  $scope.formData.date_transaction=dateNow;
  $scope.formData.jt=1;
  

 
  $scope.detail.total=0;
  $scope.formData.total=0;
  $scope.formData.disc=0;
  $scope.formData.discT=0;
  $scope.formData.pajak=0;
  $scope.formData.grand=0;
  
   $http.get(baseUrl+'/setting/tax/create').then(function(data) {
    
    $scope.data=data.data;
  });

    var urut=0
    $scope.appendTable=function() {
    var dt=$scope.detail;
    var html="";
    html+="<tr id='row-"+urut+"'>";
    html+="<td>"+$('#akun option:selected').text();+"</td>";
    html+="<td>"+$('#desc').val()+"</td>";
    html+="<td>"+$filter('number')($scope.detail.total)+"</td>";
    html+="<td><a ng-click='deleteAppend("+urut+")'><span class='fa fa-trash'></span></a></td>";
    html+="</tr>";

    $('#appendTable tbody').append($compile(html)($scope));
    $scope.formData.detail.push($scope.detail);

    $scope.detail={};
    $scope.detail.akun=0;
    $scope.detail.total=0;
    $scope.detail.description='';
    urut++;

    $scope.hitungTotalBayar();
  }

  $scope.hitungTotalBayar=function() {
    var total=0;
    angular.forEach($scope.formData.detail,function(val,i) {
      total+=parseFloat(val.total);
    });
    $scope.formData.total=total;
    $scope.hitungGrand();
  }

  $scope.deleteAppend=function(ids) {
    $('#row-'+ids).remove();
    delete $scope.formData.detail[ids];

      $scope.formData.detail_tax=[
        {id:1,tax_id:null,value:0},
        {id:2,tax_id:null,value:0},
        {id:3,tax_id:null,value:0},
        {id:4,tax_id:null,value:0},
        {id:5,tax_id:null,value:0},
      ]

    $scope.hitungTotalBayar();
    
  }

   $scope.hitungGrand=function() {
    var grand=0
    var sub=parseFloat($scope.formData.total==''?0: $scope.formData.total)
    var disc=parseFloat($scope.formData.discT==''?0: $scope.formData.discT)
    var pajak=parseFloat($scope.formData.pajak==''?0:$scope.formData.pajak)
     angular.forEach($scope.formData.detail_tax, function(val,i) {
      pajak+=parseFloat(val.value);
    })
    $scope.formData.total_tax=pajak;
    $scope.formData.pajak=pajak;
    grand=sub-disc+pajak
     $scope.formData.grand=grand;
   }

    $scope.hitungDisc=function() {
       var sub=parseFloat($scope.formData.total==''?0: $scope.formData.total)
       var disc=parseFloat($scope.formData.disc==''?0: $scope.formData.disc)
       var discT=sub*disc/100
       $scope.formData.discT=discT;
       $scope.hitungGrand();
    } 
    $scope.hitungDiscT=function() {
       var sub=parseFloat($scope.formData.total==''?0: $scope.formData.total)
       var discT=parseFloat($scope.formData.discT==''?0: $scope.formData.discT)
       var disc=(discT/sub)*100
       $scope.formData.disc=disc;
       $scope.hitungGrand();
    }

     
      $scope.formData.detail_tax=[
        {id:1,tax_id:null,value:0},
        {id:2,tax_id:null,value:0},
        {id:3,tax_id:null,value:0},
        {id:4,tax_id:null,value:0},
        {id:5,tax_id:null,value:0},
      ]
      $scope.tax=[
        {id:1,name:'PPH 23',pemotong_pemungut:1,npwp:2},
        {id:2,name:'PPH 22',pemotong_pemungut:2,npwp:5},
       
      ]
      $scope.formData.total_tax=0
          
  $scope.ppnSet=function() {
    var base="";
    base+='<table class="table table-borderless">'
    base+='<tbody>'
    angular.forEach($scope.formData.detail_tax, function(val,i) {
      base+='<tr>'
      base+='<td>'
      base+='<select class="form-control" ng-change="hitungTaxSingle('+i+',formData.detail_tax['+i+'].tax_id)" '
      base+='data-placeholder-text-single="\'Pajak 1\'" chosen allow-single-deselect="true" '
      base+='ng-model="formData.detail_tax['+i+'].tax_id" ng-options="s.id as s.name for s in tax">'
      base+='<option value=""></option>'
      base+='</select>'
      base+='</td>'
      base+='<td>'
      base+='<input type="text" class="form-control" jnumber2 only-num readonly ng-model="formData.detail_tax['+i+'].value">'
      base+='</td>'
      base+='</tr>'
    });
    base+='</tbody>'
    base+='</table>'
    base+='<div class="form-group">'
    base+='<label class="col-md-4">Total Pajak Lain</label>'
    base+='<div class="col-md-7">'
    base+='<input type="text" readonly class="form-control" jnumber2 only-num ng-model="formData.total_tax">'
    base+='</div>'

    $('#detailHtml').html($compile(base)($scope))
    $('#modalTax').modal('show');
  }

   $scope.hitungTaxSingle=function(i,id) {
     var sub=parseFloat($scope.formData.total==''?0: $scope.formData.total)
     var discT=parseFloat($scope.formData.discT==''?0: $scope.formData.discT)
     var total_with_discount=sub-discT;
    if (!id) {
      $scope.formData.detail_tax[i].value=0;
      return $scope.hitungGrand();
    }
    var jsn=$rootScope.findJsonId(id,$scope.tax);
    if (jsn.pemotong_pemungut==1) {
      var tst=-(total_with_discount*jsn.npwp/100);
    } else {
      var tst=(total_with_discount*jsn.npwp/100);
    }
    $scope.formData.detail_tax[i].value=tst;
    $scope.hitungGrand();
  }

});


app.controller('NotaHutangShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Nota Hutang";
 
});

