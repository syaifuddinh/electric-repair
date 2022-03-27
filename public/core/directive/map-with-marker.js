solog.directive('sologMapWithMarker', function () {
    return {
        restrict: 'E',
        scope: {
            isDraggable : '=',
            isRouteMode : '=',
            initLat : '=',
            initLng : '=',
            initZoom : '=',
            withPopup : '=',
        },
        transclude: true,
        templateUrl: '/core/base/map.html',
        link: function (scope, el, attr, ngModel) {
            var slugId = 'mapid-solog-'+Math.round(Math.random() * 9999999)
            setTimeout(function () {
                $('#mapid-solog').attr('id', slugId)
                scope.mymap = L.map(slugId)
                console.log('setTimeout', slugId)
            }, 600)
            
            scope.slugId = slugId
        },
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $timeout) {
            $scope.latitude = -7.331438432711705 // daerah surabaya
            $scope.longitude = 112.76870854695639
            $scope.zoom = 15
            $scope.currentAddress = null

            $scope.mapbox_token = 'pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpejY4NXVycTA2emYycXBndHRqcmZ3N3gifQ.rJcFIG214AriISLbB6B5aw'

            if($scope.initLat){
                $scope.latitude = $scope.initLat
            }
            if($scope.initLng){
                $scope.longitude = $scope.initLng
            }
            if($scope.initZoom){
                $scope.zoom = $scope.initZoom
            }
            
            if($scope.isRouteMode){
                $scope.mymap = L.map('mapid-solog');
            } else {
                $scope.mymap = L.map('mapid-solog').setView([$scope.latitude, $scope.longitude], $scope.zoom);
            }

            $scope.layer = L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}', {
                // maxZoom: 20,
                // attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, ' +
                // 	'Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
                id: 'mapbox/streets-v11',
                tileSize: 512,
                zoomOffset: -1,
                accessToken: $scope.mapbox_token
            }).addTo($scope.mymap);

            // L.latLng(-7.331438432711705, 112.76870854695639),
            // L.latLng(-7.331438432711705, 111.76870854695639),
            // L.latLng(-7.798078531355303, 110.35766601562501)

            if($scope.isRouteMode){
                $scope.wayPointsList = [];

                $scope.controlMap = L.Routing.control({
                    router: L.Routing.mapbox($scope.mapbox_token,{
                        profile : 'mapbox/driving',
                        language: 'en',
                    }),
                    plan: new L.Routing.Plan($scope.wayPointsList, {
                        draggableWaypoints: false
                    }),
                    show: false,
                    collapsible: true,
                    routeWhileDragging: false,
                    showAlternatives: false,
                    altLineOptions: {
                        styles: [
                            {color: 'black', opacity: 0.15, weight: 9},
                            {color: 'white', opacity: 0.8, weight: 6},
                            {color: 'blue', opacity: 0.5, weight: 2}
                        ]
                    }
                }).addTo($scope.mymap);
    
                L.Routing.errorControl($scope.controlMap).addTo($scope.mymap);

                $scope.$on('updateRouteMap', function(e, v){
                    for(var i in v){
                        $scope.wayPointsList.push(L.latLng(v[i].latitude, v[i].longitude))
                    }
                    $scope.updateRoute()
                })

                $scope.updateRoute = function(){
                    $scope.controlMap.setWaypoints($scope.wayPointsList)
                }
            } else {
                $scope.marker = L.marker([$scope.latitude, $scope.longitude]).addTo($scope.mymap);
                if($scope.isDraggable){
                    $scope.marker.dragging.enable()
                }
    
                $scope.getAddress = function(lat, lng, reverse){
                    var urlAddress = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2'
                    $.get(urlAddress + '&lat=' +lat+ '&lon=' + lng, function(data){
                        $scope.currentAddress = data
                        $scope.$emit('getAddress', data)
                        $scope.updateLatLng(lat, lng, reverse)
                    })
                }
            
                $scope.marker.on('dragend', function (e) {
                    $scope.getAddress($scope.marker.getLatLng().lat, $scope.marker.getLatLng().lng)
                });
                
                $scope.mymap.on('click', function (e) {
                    $scope.marker.setLatLng(e.latlng);
                    $scope.getAddress($scope.marker.getLatLng().lat, $scope.marker.getLatLng().lng)
                });
    
                $scope.$on('updateLatLng', function(e, v){
                    $scope.getAddress(v.lat, v.lng, true);
                })
                
                $scope.updateLatLng = function(lat,lng,reverse) {
                    if(reverse) {
                        $scope.marker.setLatLng([lat,lng]);
                        $scope.mymap.panTo([lat,lng]);
                    } else {
                        $scope.$emit('getLatLng', $scope.marker.getLatLng())
                        $scope.mymap.panTo([lat,lng]);
                    }
    
                    if($scope.withPopup){
                        var popup_map = null
                        if($scope.currentAddress != null){
                            popup_map = $scope.currentAddress.display_name + '. ' + $scope.marker.getLatLng().toString()
                        } else {
                            popup_map = $scope.marker.getLatLng().toString()
                        }
    
                        $scope.marker.bindPopup(popup_map).openPopup();
                    }
                }
            }
        }
    }
});