app.controller('marketingReport', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Report Marketing";
  $scope.formData={}
  $scope.formData.report_id=1

  $scope.reports=[
    {id:1,name:"Executive Summary"},
    {id:2,name:"Marketing"},
    {id:3, name : 'Freight Forwarding'}
  ]

  $scope.date=new Date;
  // console.log($scope.date)
  $scope.year_f=function() {
    var yr=2000;
    var year_arr=[]
    for (var i = 0; i < 50; i++) {
      year_arr.push({id:yr+i,name:yr+i})
    }
    return year_arr;
  }

  $scope.year=$scope.year_f()
  $scope.reportChange=function(formData) {
    $scope.formData={}
    $scope.formData.report_id=formData.report_id;
    if (formData.report_id==2) {
      $scope.formData.year=$scope.date.getFullYear()
    }
  }

  $scope.service_type=[]
  $http.get(baseUrl+'/marketing/report').then(function(data) {
    $scope.data=data.data;

    angular.forEach(data.data.service_type, function(val,i) {
      $scope.service_type.push({id:i,name:val})
    })
  });

  $scope.submitForm=function() {
    $http({method: 'POST', url: baseUrl+'/marketing/report/export', params: $scope.formData, responseType: 'arraybuffer'})
    .then(function successCallback(data, status, headers, config) {
      
      headers = data.headers()
      var filename = headers['content-disposition'].split('"')[1];
      var contentType = headers['content-type'];
      var linkElement = document.createElement('a');
      try {
        var blob = new Blob([data.data], { type: contentType });
        var url = window.URL.createObjectURL(blob);
        linkElement.setAttribute('href', url);
        linkElement.setAttribute("download", filename);
        var clickEvent = new MouseEvent("click", {
          "view": window,
          "bubbles": true,
          "cancelable": false
        });
        linkElement.dispatchEvent(clickEvent);
      } catch (e) {
        
      }
    },
    function errorCallback(data, status, headers, config) {
      
      toastr.error(data.statusText);
    });
  }

  $scope.resetFilter=function(formData) {
    $scope.formData={}
    $scope.formData.report_id=formData.report_id
    $scope.printHtml()
  }

  $scope.printHtml=function() {
    $http.post(baseUrl+'/marketing/report/report_html',$scope.formData).then(function(data) {
      var dt=data.data.item;
      var sgroup=data.data.service_group;
      var dtall=data.data;
      var html="";
      if ($scope.formData.report_id==1) {
        html+="<div class='row'>"
        html+="<div class='col-md-12 text-center'>"
        html+="<h2 class='font-bold'>LAPORAN EXECUTIVE SUMMARY</h2>"
        html+="<h4 class='font-bold'>"+(dtall.company?dtall.company.name:'Semua Cabang')+"</h4>"
        html+="<h5 class='font-bold'>"+(dtall.customer?dtall.customer.name:'Semua Customer')+"</h5>"
        if ($scope.formData.start_date && $scope.formData.end_date) {
          html+="<h5 class='font-bold'>Periode "+$scope.formData.start_date+" s/d "+$scope.formData.end_date+"</h5>"
        }
        html+="<div class='col-md-6'>"
        for (var i = 0; i < 3; i++) {
          var datar=dt[i];
          html+="<table class='table table-borderless'><tbody>"
          html+="<tr>"
          html+="<td colspan='2'><h5>"+$rootScope.findJsonId(i+1,sgroup).name+"</h5></td>"
          html+="</tr>"

          html+="<tr>"
          html+="<td style='width:70%;'>Jumlah WO</td>"
          html+="<td class='text-right' > "+datar.work_order_total+"</td>"
          html+="</tr>"

          html+="<tr>"
          html+="<td>Jumlah JO</td>"
          html+="<td class='text-right' > "+datar.job_order_total+"</td>"
          html+="</tr>"

          html+="<tr>"
          html+="<td >Qty</td>"
          html+="<td class='text-right' style='border-bottom:1px solid;'> "+datar.qty_total+"</td>"
          html+="</tr>"

          html+="<tr>"
          html+="<td>Total Pendapatan</td>"
          html+="<td class='text-right' > "+$filter('number')(datar.pendapatan_total)+"</td>"
          html+="</tr>"

          html+="<tr>"
          html+="<td>Total Biaya</td>"
          html+="<td class='text-right' > "+$filter('number')(datar.biaya_total)+"</td>"
          html+="</tr>"

          html+="<tr>"
          html+="<td>Total Keuntungan</td>"
          html+="<td class='text-right font-bold' style='border-top: 1px solid; border-bottom:2px double;' > "+$filter('number')(datar.pendapatan_total-datar.biaya_total)+"</td>"
          html+="</tr>"

          html+="</tbody></table>"
          html+="<hr>"
        }
        html+="</div>"
        html+="<div class='col-md-6'>"
        for (var i = 3; i < 6; i++) {
          var datar=dt[i];
          html+="<table class='table table-borderless'><tbody>"
          html+="<tr>"
          html+="<td colspan='2'><h5>"+$rootScope.findJsonId(i+1,sgroup).name+"</h5></td>"
          html+="</tr>"

          html+="<tr>"
          html+="<td style='width:70%;'>Jumlah WO</td>"
          html+="<td class='text-right' > "+datar.work_order_total+"</td>"
          html+="</tr>"

          html+="<tr>"
          html+="<td>Jumlah JO</td>"
          html+="<td class='text-right' > "+datar.job_order_total+"</td>"
          html+="</tr>"

          html+="<tr>"
          html+="<td >Qty</td>"
          html+="<td class='text-right' style='border-bottom:1px solid;'> "+datar.qty_total+"</td>"
          html+="</tr>"

          html+="<tr>"
          html+="<td>Total Pendapatan</td>"
          html+="<td class='text-right' > "+$filter('number')(datar.pendapatan_total)+"</td>"
          html+="</tr>"

          html+="<tr>"
          html+="<td>Total Biaya</td>"
          html+="<td class='text-right' > "+$filter('number')(datar.biaya_total)+"</td>"
          html+="</tr>"

          html+="<tr>"
          html+="<td>Total Keuntungan</td>"
          html+="<td class='text-right font-bold' style='border-top: 1px solid; border-bottom:2px double;' > "+$filter('number')(datar.pendapatan_total-datar.biaya_total)+"</td>"
          html+="</tr>"

          html+="</tbody></table>"
          html+="<hr>"
        }
        html+="</div>"
        html+="</div>"
        html+="</div>"
      }
      else if($scope.formData.report_id==2) {
        html+="<div class='row'>"
        html+="<div class='col-md-12 text-center'>"
        html+="<h2 class='font-bold'>LAPORAN MARKETING</h2>"
        html+="<h4 class='font-bold'>"+(dtall.company?dtall.company.name:'Semua Cabang')+"</h4>"
        html+="<h4 class='font-bold'>Tahun "+$scope.formData.year+"</h4>"
        html+="<table class='table table-bordered'>"
        html+="<thead class='thcenter'>"
        html+="<tr>"
        html+="<th>BULAN</th>"
        html+="<th>LEAD</th>"
        html+="<th>LEAD FAILED</th>"
        html+="<th>OPPORTUNITY</th>"
        html+="<th>OPPORTUNITY FAILED</th>"
        html+="<th>INQUERY</th>"
        html+="<th>INQUERY FAILED</th>"
        html+="<th>QUOTATION</th>"
        html+="<th>QUOTATION FAILED</th>"
        html+="<th>WORK ORDER</th>"
        html+="</tr>"
        html+="</thead>"
        html+="<tbody>"
        angular.forEach(dt, function(val,i) {
          html+="<tr>"
          html+="<td class='font-bold'>"+val.month+"</td>"
          html+="<td class='text-right'>"+$filter('number')(val.lead.lead)+"</td>"
          html+="<td class='text-right'>"+$filter('number')(val.lead.lead_failed||'')+"</td>"
          html+="<td class='text-right'>"+$filter('number')(val.opportunity_inquery.opportunity_success||'')+"</td>"
          html+="<td class='text-right'>"+$filter('number')(val.opportunity_inquery.opportunity_failed||'')+"</td>"
          html+="<td class='text-right'>"+$filter('number')(val.opportunity_inquery.inquery_success||'')+"</td>"
          html+="<td class='text-right'>"+$filter('number')(val.opportunity_inquery.inquery_failed||'')+"</td>"
          html+="<td class='text-right'>"+$filter('number')(val.quotation.quotation||'')+"</td>"
          html+="<td class='text-right'>"+$filter('number')(val.quotation.quotation_failed||'')+"</td>"
          html+="<td class='text-right'>"+$filter('number')(val.wo.wo||'')+"</td>"
          html+="</tr>"
        })
        html+="</tbody>"
        html+="</div>"
        html+="</div>"
      }
      else {
        var find_service = function(scope) {
          var services = scope.data.service_group;
          var service_id = scope.formData.service_group_id;
          var service = services.find(function(unit){
            return unit.id == service_id;
          });

          outp = service.name;
          return outp;
        }
        var service_name = $scope.formData.service_group_id != undefined ? 'Layanan ' + find_service($scope) : 'Semua Layanan';
        var start_date = $scope.formData.start_date;
        var end_date = $scope.formData.end_date;
        var periode = start_date != '' && end_date != '' ? 'Periode ' + start_date + " s/d " + end_date : 'Semua Periode';


        html+="<div class='row'>"
        html+="<div class='col-md-12 text-center table-responsive'>"
        html+="<h2 class='font-bold'>LAPORAN FREIGHT FORWARDING</h2>"
        html+="<h4 class='font-bold'>"+service_name+"</h4>"
        html+="<h4 class='font-bold'>" + periode + "</h4>"
        html+="<table class='table table-bordered'>"
        html+="<thead class='thcenter'>"
        html+="<tr>"
        html+="<th>NO</th>"
        html+="<th>Customer</th>"
        html+="<th>No. WO</th>"
        html+="<th>Tanggal</th>"
        html+="<th>Layanan</th>"
        html+="<th>QTY</th>"
        html+="<th>Satuan</th>"
        html+="<th>Pendapatan</th>"
        html+="<th>Biaya Operasional</th>"
        html+="<th>Biaya Reimburse</th>"
        html+="<th>Profit</th>"
        html+="<th>Persentase</th>"
        html+="</tr>"
        html+="</thead>"
        html+="<tbody>"
        var increment,qty=0,pendapatan=0,operasional=0,reimburse=0,profits=0;
        if(dt.length > 0) {
          angular.forEach(dt, function(val,i) {
            increment = i + 1;
            var profit=val.invoice_price-val.operational_price
            var percent=Math.round(profit/val.invoice_price*100)
            html+="<tr>"
            html+="<td class='font-bold'>"+increment+"</td>"
            html+="<td class='font-bold'>"+ val.customer +"</td>"
            html+="<td class='font-bold'>"+ val.code_wo +"</td>"
            html+="<td class=''>"+val.date_wo+"</td>"
            html+="<td class='text-right'>"+(val.type==1?val.service_name:val.service_name_pl)+"</td>"
            html+="<td class='text-right'>"+$filter('number')(val.qty)+"</td>"
            html+="<td class=''>"+(val.type==1?val.pieces_name:val.pieces_name_pl)+"</td>"
            html+="<td class='text-right'>"+$filter('number')(val.invoice_price)+"</td>"
            html+="<td class='text-right'>"+$filter('number')(val.operational_price)+"</td>"
            html+="<td class='text-right'>"+$filter('number')(val.talangan_price)+"</td>"
            html+="<td class='text-right'>"+$filter('number')(profit)+"</td>"
            html+="<td class='text-right'>"+$filter('number')(percent)+" %</td>"
            html+="</tr>"
            pendapatan+=parseFloat(val.invoice_price)
            operasional+=parseFloat(val.operational_price)
            reimburse+=parseFloat(val.talangan_price)
            profits+=parseFloat(profit)
          })
          var percentTotal=Math.round(profits/pendapatan*100)
          html+="<tfoot><tr>"
          html+="<th colspan='7'>TOTAL</th>"
          html+="<th class='text-right'>"+$filter('number')(pendapatan)+"</th>"
          html+="<th class='text-right'>"+$filter('number')(operasional)+"</th>"
          html+="<th class='text-right'>"+$filter('number')(reimburse)+"</th>"
          html+="<th class='text-right'>"+$filter('number')(profits)+"</th>"
          html+="<th class='text-right'>"+$filter('number')(percentTotal)+" %</th>"
          html+="</tr></tfoot>"

        } else {
          html += "<tr colspan='11'>Tidak Ada Data</tr>";
        }
        html+="</tbody>"
        html+="</div>"
        html+="</div>"
      }
      $('#content').html(html)
    });
  }
});
