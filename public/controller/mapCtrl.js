var app = angular.module('app', ['localytics.directives'],function($interpolateProvider) {
  $interpolateProvider.startSymbol('<%');
  $interpolateProvider.endSymbol('%>');
})

app.run(function($rootScope) {
  $rootScope.findJsonId=function(value,jsons,key='id') {
    if (!jsons || value==null || value==undefined) {
      return {}
    }
    for (var i = 0; i < jsons.length; i++) {
      if (jsons[i][key]==value) {
        return jsons[i]
      }
    }
    return {}
  }
})

app.controller('mapController', function($scope,$http,$interval,$filter,$rootScope) {
  $http.defaults.headers.common['Authorization']='Bearer '+apiToken
  // var baseUrl="http://localhost/bcs"
  var baseUrl="http://178.128.18.112/bcs"
  // var baseUrl="http://"+window.location.hostname
  var vehicleMarker=[]
  $scope.searchData={}
  $scope.listDriver={}
  $scope.listDriver.online=0
  $scope.listJob={}
  $scope.listJob.total_job=0
  $scope.listJob.progress=0
  $scope.listJob.selesai=0

  $scope.drivers=[]

  var map = new GMaps({
      div: '#map',
      lat: -5.9793438,
      lng: 106.0075616,
      zoom: 15,
      scrollwheel: true,
      streetViewControl: false,
      fullscreenControl: false,
    });

  map.addLayer('traffic');
  function gps2() {
    $http.post(baseUrl+'/api/operational/get_last_position_by_vendor_2').then(function(data) {
      var dt=data.data;
      angular.forEach(dt,function(val,i) {
        $scope.drivers.push({id:'iT'+i,name:val.VehicleId+' ( inovaTrack )'})
        var icons=""
        if (val.Engine) {
          icons=baseUrl+'/img/truck_green.png';
        } else {
          icons=baseUrl+'/img/truck_red.png';
        }
        var date=new Date(val.DateTime)
        var ct=""
        ct+="<table style='width:400px;'><tbody>"
        ct+="<tr>"
        ct+="<td>No. Pol</td>"
        ct+="<td> : <b>"+val.VehicleId+"</b></td>"
        ct+="</tr>"
        ct+="<tr>"
        ct+="<td>Current Position</td>"
        ct+="<td> : "+val.StreetName+","+val.Kecamatan+","+val.Kabupaten+"</td>"
        ct+="</tr>"
        ct+="<tr>"
        ct+="<td>Last Update</td>"
        ct+="<td> : "+$filter('date')(date,'dd-MM-yyyy HH:mm')+"</td>"
        ct+="</tr>"
        ct+="<tr>"
        ct+="<td>Vehicle Status</td>"
        ct+="<td> : "+(val.Engine?'ON':'OFF')+"</td>"
        ct+="</tr>"
        ct+="</tr>"
        ct+="<td>Source</td>"
        ct+="<td> : InnovaTrack</td>"
        ct+="</tr>"
        ct+="</tbody></table>"
        console.log(val.Y, val.X)
        vehicleMarker.push(
          map.addMarker({
            lat:val.Y,
            lng:val.X,
            icon:icons,
            details:{
              driver_id: 'IT'+i,
              name:val.VehicleId,
              nopol:val.VehicleId,
              lat:val.Y,
              lng:val.X,
              last_update: new Date(val.DateTime)
            },
            infoWindow: {
              content:ct
            }
          })
        )
      })
    })
  }

  $scope.job_status=[
    {id:1,name:'<span class="badge badge-success">Assigned To Vendor</span>'},
    {id:2,name:'<span class="badge badge-success">Assigned To Driver</span>'},
    {id:3,name:'<span class="badge badge-warning">Terima Job</span>'},
    {id:4,name:'<span class="badge badge-warning">Pengambilan</span>'},
    {id:5,name:'<span class="badge badge-warning">Tiba di Lokasi Pengambilan</span>'},
    {id:6,name:'<span class="badge badge-warning">Muat Barang</span>'},
    {id:7,name:'<span class="badge badge-warning">Berangkat</span>'},
    {id:8,name:'<span class="badge badge-warning">Tiba Dilokasi</span>'},
    {id:9,name:'<span class="badge badge-warning">Bongkar Muatan</span>'},
    {id:10,name:'<span class="badge badge-primary">Selesai Bongkar</span>'},
    {id:11,name:'<span class="badge badge-primary">Selesai</span>'},
    {id:12,name:'<span class="badge badge-danger">Dibatalkan</span>'},
    {id:13,name:'<span class="badge badge-danger">Ditolak</span>'},
  ]

  function gps1() {
    $http.post(baseUrl+'/api/operational/get_last_position_by_vendor_1').then(function(data) {
      var dt=data.data;
      angular.forEach(dt,function(val,i) {
        console.log($rootScope.findJsonId(val.job_status_id,$scope.job_status))
        $scope.drivers.push({id:'eG'+i,name:val.no_pol+' ('+ (val.driver?val.driver:'-') +') ( EasyGo )'})
        var icons=""
        if (val.acc=="ON") {
          icons=baseUrl+'/img/truck_green.png';
        } else {
          icons=baseUrl+'/img/truck_red.png';
        }
        var date=new Date(val.gps_time)
        var jobContent="<table class=\"table table-striped table-small\"><tbody>"
        jobContent+="<thead>"
        jobContent+="<tr>"
        jobContent+="<th>No. SJ</th>"
        jobContent+="<th>Trayek</th>"
        jobContent+="<th>Driver</th>"
        jobContent+="<th>Status</th>"
        jobContent+="</tr>"
        jobContent+="</thead>"
        jobContent+="<tbody>"
        if (val.delivery_id) {
          jobContent+="<tr>"
          jobContent+="<td><a target='_blank' href='"+baseUrl+"/#!/operasional/surat_jalan_driver/"+val.delivery_id+"'>"+val.code_sj+"</a></td>"
          jobContent+="<td>"+val.trayek+"</td>"
          jobContent+="<td>"+(val.driver?val.driver:'-')+"</td>"
          jobContent+="<td>"+(val.job_status_id ? $rootScope.findJsonId(val.job_status_id,$scope.job_status).name :'-')+"</td>"
          jobContent+="</tr>"
        } else {
          jobContent+="<tr>"
          jobContent+="<td colspan='4'>This vehicle don't have a job.</td>"
          jobContent+="</tr>"
        }
        jobContent+="</tbody></table>"

        var ct=""
        ct+="<table style='width:400px;'><tbody>"
        ct+="<tr>"
        ct+="<td>No. Pol</td>"
        ct+="<td> : <b>"+val.no_pol+"</b></td>"
        ct+="</tr>"
        ct+="<tr>"
        ct+="<td>Current Position</td>"
        ct+="<td> : "+val.address+"</td>"
        ct+="</tr>"
        ct+="<tr>"
        ct+="<td>Last Update</td>"
        ct+="<td> : "+$filter('date')(date,'dd-MM-yyyy HH:mm')+"</td>"
        ct+="</tr>"
        ct+="<tr>"
        ct+="<td>Vehicle Status</td>"
        ct+="<td> : "+val.acc+"</td>"
        ct+="</tr>"
        ct+="</tr>"
        ct+="<td>Source</td>"
        ct+="<td> : EasyGo</td>"
        ct+="</tr>"
        ct+="</tbody></table>"
        ct+="<h5>Surat Jalan Terakhir</h5>"
        ct+=jobContent
        vehicleMarker.push(
          map.addMarker({
            lat:val.latitude,
            lng:val.longitude,
            icon:icons,
            details:{
              driver_id: 'eG'+i,
              name:val.no_pol,
              nopol:val.no_pol,
              lat:val.latitude,
              lng:val.longitude,
              last_update: new Date(val.gps_time)
            },
            infoWindow: {
              content:ct
            }
          })
        )
      })
    })
  }

  $scope.driverJobList=function() {
    $http.get(baseUrl+'/api/operational/map_driver_job_list').then(function(data){
      // $scope.data=data.data
      $scope.listJob.total_job=0
      $scope.listJob.progress=0
      $scope.listJob.selesai=0
      $scope.drivers=[]
      angular.forEach(data.data.driver,function(val,i) {
        $scope.drivers.push({id:val.id,name:val.name+' - '+val.nopol})
        map.removeMarkers()
        baseMarker()

        var jobContent="<table class=\"table table-striped table-small\"><tbody>"
        jobContent+="<thead>"
        jobContent+="<tr>"
        jobContent+="<th>No. SJ</th>"
        jobContent+="<th>Status</th>"
        jobContent+="<th>Update Terakhir</th>"
        jobContent+="</tr>"
        jobContent+="</thead>"
        jobContent+="<tbody>"
        var totJob=0
        angular.forEach(data.data.job, function(vv,x) {
          if (vv.driver_id==val.id && !vv.is_finish) {
            totJob++
            jobContent+="<tr>"
            jobContent+="<td>"+vv.no_sj+"</td>"
            jobContent+="<td>"+vv.status+"</td>"
            jobContent+="<td>"+vv.last_update+"</td>"
            jobContent+="</tr>"
          }
        })
        if (!totJob) {
          jobContent+="<tr>"
          jobContent+="<td colspan='3'>Driver ini tidak memiliki pekerjaan.</td>"
          jobContent+="</tr>"
        }
        jobContent+="</tbody></table>"
        if (val.lat && val.lng) {
          vehicleMarker.push(
            map.addMarker({
              lat:val.lat,
              lng:val.lng,
              icon:baseUrl+'/img/truck_green.png',
              details: {
                driver_id: val.id,
                name:val.name,
                nopol:val.nopol,
                lat:val.lat,
                lng:val.lng,
                last_update: new Date(val.last_update)
              },
              infoWindow: {
                content: "<h4>"+val.name+" ("+val.nopol+")</h4><h5>List Pekerjaan Hari Ini</h5>"+jobContent
              }
            })
          )
        }
      })
      angular.forEach(data.data.job,function(val,i) {
        if (val.is_finish) {
          $scope.listJob.selesai+=1
        } else {
          $scope.listJob.progress+=1
        }
      })
      $scope.listJob.total_job=$scope.listJob.progress+$scope.listJob.selesai
      $scope.listDriver.online=data.data.driver.length

      $scope.findDriver()
    },function(error){
      // console.log(error.message)
    })
  }

  $scope.findDriver=function() {
    var iddriver=$scope.searchData.driver_id
    // return null;
    if (iddriver) {
      angular.forEach(map.markers, function(val,i) {
        if (iddriver==val.details.driver_id) {
          val.infoWindow.open(map, val);
          map.setCenter(val.details.lat, val.details.lng);
          map.setZoom(17)
          return null;
        }
      })
    } else {
      // console.log('Driver tidak ditemukan')
    }
  }

  function baseMarker() {
    map.addMarker({
      lat:-5.9793438,
      lng:106.0075616,
      icon:baseUrl+'/img/head.png',
      details: {
        driver_id:0
      }
    })
  }
  baseMarker()

  $scope.driverJobList()
  gps1()
  gps2()
  $interval(function() {
    map.removeMarkers(vehicleMarker);
    vehicleMarker=[]
    $scope.driverJobList() // juga untuk refresh market map
    gps1()
    gps2()
  },60000)
})
