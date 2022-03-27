app.controller('homeIndex', function($scope, $http, $rootScope, $compile, $filter) {
  $rootScope.pageTitle='Dashboard';
  $scope.reportDate = dateNow
  $scope.WOReportType = 'month'

  $('.stat-customer').children('.ibox-content').toggleClass('sk-loading');
  $('.stat-lead').children('.ibox-content').toggleClass('sk-loading');
  $('.stat-invoice').children('.ibox-content').toggleClass('sk-loading');
  $('.stat-wo').children('.ibox-content').toggleClass('sk-loading');
  $('.stat-jo').children('.ibox-content').toggleClass('sk-loading');
  $('.stat-balance').children('.ibox-content').toggleClass('sk-loading');
  $('.stat-gross').children('.ibox-content').toggleClass('sk-loading');
  $('.stat-woTrend').children('.ibox-content').toggleClass('sk-loading');

  $http.get(baseUrl+'/api/finance/gross_margin_inPercent', {params:{date:$scope.reportDate}}).then(function(response) {
    $scope.gross_margin=response.data.data
    $scope.totalIncomeGross=response.data.total_income
    $scope.totalCostGross=response.data.total_cost
    $('.stat-gross').children('.ibox-content').toggleClass('sk-loading');
  })
  $http.get(baseUrl+'/api/finance/balance_inPercent', {params:{date:$scope.reportDate}}).then(function(response) {
    $scope.balance=response.data.balance
    $scope.company_name=response.data.company_name
    $('.stat-balance').children('.ibox-content').toggleClass('sk-loading');
  })
  $http.get(baseUrl+'/api/contact/customer_amount').then(function(response) {
    $scope.customerAmount=response.data.data.all
    $scope.newCustomerAmount=response.data.data.new
    $('.stat-customer').children('.ibox-content').toggleClass('sk-loading');
  })
  $http.get(baseUrl+'/api/marketing/lead_amount').then(function(response) {
    $scope.leadAmount=response.data.data.lead
    $scope.looseLeadAmount=response.data.data.loose
    $('.stat-lead').children('.ibox-content').toggleClass('sk-loading');
  })
  $http.get(baseUrl+'/api/operational/invoice_jual_amount', {params:{date:$scope.reportDate}}).then(function(response) {
    $scope.invoiceAmount=response.data.data.all.count
    $scope.invoiceTotal=response.data.data.all.summary
    $scope.unpaidInvoiceAmount=response.data.data.unpaid.count
    $scope.unpaidInvoiceTotal=response.data.data.unpaid.summary

    $('.stat-invoice').children('.ibox-content').toggleClass('sk-loading');
  })
  $http.get(baseUrl+'/api/operational/job_order_inProgress', {params:{date:$scope.reportDate}}).then(function(response) {
    let data=response.data.data
    let total=data.total_done+data.total_process
    let totalProses=data.total_process
    let totalDone=data.total_done

    $scope.percentageJOInProgress = ((totalProses / total)*100).toFixed(2)
    $('.stat-jo').children('.ibox-content').toggleClass('sk-loading');
  })
  $http.get(baseUrl+'/api/marketing/work_order_amount/${$scope.WOReportType}', {params:{date:$scope.reportDate}}).then(function(response) {
    if ($scope.WOReportType == 'date') {
      $scope.rawDataWOAmountDay = response.data.data
    } else {
      $scope.rawDataWOAmountMonth = response.data.data
    }

    $scope.showGraph()
    $('.stat-wo').children('.ibox-content').toggleClass('sk-loading');
  })
  // $http.get(`${baseUrl}/api/marketing/work_order_trend`, {params:{date:$scope.reportDate}}).then(function(response) {
  //   $scope.showLineGraph(response.data.data)
  //   $('.stat-woTrend').children('.ibox-content').toggleClass('sk-loading');
  // })
  $http.get(`${baseUrl}/api/marketing/work_order_trend_new`, {params:{date:$scope.reportDate}}).then(function(response) {
    // $scope.showLineGraph(response.data.data)
    $scope.chartLine(response.data);
    // console.log(response)
    $('.stat-woTrend').children('.ibox-content').toggleClass('sk-loading');
  })

  $scope.lineGraph=function(data) {
    var chart_data=[]
    angular.forEach(data, function(val,i) {
      var dataset=[]
      angular.forEach(val.tanggal,function(xx,x) {
        dataset.push({x:new Date(x),y:xx})
      })

      chart_data.push({
        name:val.nama,
        type:'spline',
        yValueFormatString: '#0',
        showInLegend: true,
        dataPoints:dataset
      })
    })
    // console.log(chart_data)

    var chartS=new CanvasJS.Chart("work_order_trend_new",{
      animationEnabled:true,
      axisX:{
        yValueFormatString:"DD MM,YY"
      },
      axisY:{
        title:"Jumlah Work Order",
        includeZero:true,
      },
      legend:{
        cursor: "pointer",
        fontSize: 16,
      },
      toolTip:{
        shared: true
      },
      data:chart_data
    })
    chartS.render()
  }

  $scope.renderGrafik=function(results, place, width, query_name) {
    var paper = $('<div class="ibox"></div>')
    paper.addClass('col-md-' + width)
    paper.append(
        $('<div class="ibox-title"><h5>' + query_name + '</h5></div>')
    ) 
    var container = $('<div class="ibox-content"></div>')
    var subcontainer = $('<canvas></canvas>')
    container.append(subcontainer)
    var ctx = subcontainer[0].getContext('2d');
    var labels = []
    var data = []
    for(x in results) {
        labels.push(x)
        data.push(results[x])
    }
    var charts = new Chart(ctx, {
      type: 'line',
      data: {
        'labels': labels,
        datasets: [
          {
            'data' : data,
            
          }
        ]
      },
      options:{
        scales: {
          xAxes: [{
            // stacked: true,
            gridLines: {
              color: 'rgba(0,0,0,0)'
            }
          }]
        },
        tooltips: {
          mode: 'index'
        },
        legend:{
          display:false
        }
      }

    })

    paper.append(container)
    place.append(paper)
  }

  function getRandomNumber(min, max) {
    min = Math.ceil(min);
    max = Math.floor(max);
    return Math.floor(Math.random() * (max - min + 1)) + min;
  }

  $scope.date=new Date();
  $scope.monthName=[
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
  ]

  $scope.chartLine=function(data) {
    var chartData=[]
    angular.forEach(data, function(val,i) {
      chartData.push({
        label: val.name,
        data:val.data,
        backgroundColor: 'rgba('+getRandomNumber(0,255)+','+getRandomNumber(0,255)+','+getRandomNumber(0,255)+',0.3)',
        borderWidth:1
      })
    })
    var ctx = document.getElementById('woTrendNew').getContext('2d');
    var charts = new Chart(ctx, {
      type: 'line',
      data: {
        labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'],
        datasets: chartData
      },
      options:{
        scales: {
          xAxes: [{
            // stacked: true,
            gridLines: {
              color: 'rgba(0,0,0,0)'
            }
          }]
        },
        tooltips: {
          mode: 'index'
        }
      }

    })
  }

  $scope.showGraph=function(){
    let filter = {}
    let rawData = []
    let totalAmount=0
    let totalSummary=0
    let done=0
    let doneSummary=0
    let percentageDone=0

    if($scope.WOReportType == 'date'){
      filter = {date: $scope.reportDate}
      rawData = $scope.rawDataWOAmountDay
    }else if($scope.WOReportType == 'month'){
      let reportDate = $scope.reportDate.split('-')
      // console.log(Number(reportDate[1]))
      filter = {month: Number(reportDate[1])}
      rawData = $scope.rawDataWOAmountMonth
    }

    let sourceGraph = $filter('filter')(rawData, filter, true)
    var kalender = new Date()
    var month = kalender.getMonth()
    // console.log(sourceGraph)
    let datas = sourceGraph[0].data
    if (datas.length == 0) {
      data=0
    }else {
      datas.forEach(function(d){
        totalAmount+=d.totalAmount
        totalSummary+=d.summary
        if (d.status == 2) {
          done+=d.totalAmount
          doneSummary+=d.summary
        }
      })
    }

    $scope.WOAmount=totalAmount
    $scope.WOSummary=totalSummary
    $scope.WOAmountAchieve=done
    $scope.WOSummaryAchieve=Math.round(doneSummary)
    percentageDone=Math.round((done/totalAmount)*100)

    let chart = c3.generate({
        bindto: '#WOChart',
        data: {
            columns: [
                ['data', percentageDone]
            ],
            type: 'gauge',
        },
        color: {
            pattern: ['#FF0000', '#F97600', '#F6C600', '#60B044'], // the three color levels for the percentage values.
            threshold: {
                values: [30, 60, 90, 100]
            }
        },
        size: {
            height: 180
        }
    });
  }
  $scope.showLineGraph=function(data){
    let datas = [];
    if (data.length == 0) {
      datas=[]
    }else {
      for (let d in data) {
          arr = data[d].datas
          arr.unshift(data[d].service)
          datas.push(arr)
      }
    }

    let lineChart = c3.generate({
        bindto: '#WOTrendChart',
        data: {
            columns: datas
        }
    });
  }

  var company_saldo_datatable = $('#company_saldo_datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/finance/company_saldo_datatable'
    },
    columns:[
      {data:"name",name:"name"},
      {
        data:"amount",
        name:"amount",
        className : 'text-right'
      },
    ],
  });

  $scope.showDashboard = function(dashboards) {
      var dashboard, dashboardItem
      var dashboardBuilder = $('#dashboard-builder')
      for(x in dashboards) {
          dashboard = dashboards[x]
          dashboardItem = $('<div class="dashboard-item"></div>')
          dashboardBuilder.append(dashboardItem)
          $scope.getDashboardDetail(dashboard.id, dashboardItem)
      }
  }

  $scope.renderTable = function(results, place, width) {
      var ibox = $('<div class="ibox col-md-' + width + ' "></div>')
      var container = $('<div class="ibox-content"> <div class="col-md-12 place"> <table class="table"><thead><tr></tr></thead> <tbody></tbody> </table> </div> </div>')
      var sample = results[0]
      for(x in sample) {
          container.find('thead tr').append(
              $('<th>' + x + '</th>')
          )
      }

      for(i in results) {
          tr = $('<tr></tr>')
          for(j in results[i]) {
              td = $('<td>' + results[i][j] + '</td>')
              tr.append(td)
          }

          container.find('tbody').append(
              tr
          )
      }

      ibox.append(container)
      place.append(ibox)
  }

  $scope.renderNilai = function(results, place, width, title) {
      var paper = $('<div class="ibox"></div>')
      paper.addClass('col-md-' + width)
      paper.append(
          $('<div class="ibox-title"><h5>' + title + '</h5></div>')
      ) 
      var container = $('<div class="ibox-content"></div>')
      var subcontainer = $('<div></div>')
      var sample = results
      var n = 0
      for(x in sample) {
          if(n == 0) {
              subcontainer.append(
                  $("<h1 class='no-margins '>" + sample[x] + "</h1><br>")
              )
              subcontainer.append(
                  $("<small class=''>" + x + "</small>")
              )
          }
          ++n;
      }
      container.append(subcontainer)
      paper.append(container)
      place.append(paper)
  }

  $scope.renderDashboard = function(query, type, place, width, obj) {
      $http.get(baseUrl+'/setting/query/run?query=' + query).then(function success(data) {
         var results = data.data
         switch(type) {
            case 'tabel':
                $scope.renderTable(results, place, width)
                break
            case 'nilai':
                $scope.renderNilai(results[0], place, width, obj.query_name)
                break

            case 'grafik':
                $scope.renderGrafik(results[0], place, width, obj.query_name)
                break

         }
      }, function error(data) {
      });
  }

  $scope.getDashboardDetail = function(dashboard_id, place) {
      $http.get(baseUrl+'/setting/dashboard/' + dashboard_id + '/detail').then(function success(data) {
        var details = data.data
        var detail
        for(x in details) {
            detail = details[x]
            $scope.renderDashboard(detail.query, detail.type, place, detail.width, detail)
        }
      }, function error(data) {
        $scope.getDashboardDetail(dashboard_id, place)
      });
  }

  $scope.getDashboard = function() {
      $http.get(baseUrl+'/setting/dashboard').then(function success(data) {
        var dashboards = data.data
        $scope.showDashboard(dashboards)
      }, function error(data) {
        $scope.getDashboard()
      });
  }
  $scope.getDashboard()
});
