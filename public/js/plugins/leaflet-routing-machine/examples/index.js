var map = L.map('map');

// L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/streets-v11/tiles/{z}/{x}/{y}?access_token=pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpejY4NXVycTA2emYycXBndHRqcmZ3N3gifQ.rJcFIG214AriISLbB6B5aw', {
	attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);
console.log(L.Routing)
// var control = L.Routing.control(L.extend(window.lrmConfig, {
var control = L.Routing.control({
	// waypoints: [
	// 	L.latLng(-7.331438432711705, 112.76870854695639),
	// 	L.latLng(-7.331438432711705, 111.76870854695639)
	// ],
	// geocoder: L.Control.Geocoder.nominatim(),
	// routeWhileDragging: false,
	// reverseWaypoints: true,
	// showAlternatives: true,
	// altLineOptions: {
	// 	styles: [
	// 		{color: 'black', opacity: 0.15, weight: 9},
	// 		{color: 'white', opacity: 0.8, weight: 6},
	// 		{color: 'blue', opacity: 0.5, weight: 2}
	// 	]
	// }
	router: L.Routing.mapbox('pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpejY4NXVycTA2emYycXBndHRqcmZ3N3gifQ.rJcFIG214AriISLbB6B5aw',{
        profile : 'mapbox/driving',
        language: 'en',
    }),
    waypoints: [
        L.latLng(-7.331438432711705, 112.76870854695639),
        L.latLng(-7.342438432711705, 112.66890854695639),
        L.latLng(-7.331438432711705, 111.76870854695639)
    ],
    showAlternatives: true,
    routeWhileDragging: false,
    altLineOptions: {
        styles: [
            {color: 'black', opacity: 0.15, weight: 9},
            {color: 'white', opacity: 0.8, weight: 6},
            {color: 'blue', opacity: 0.5, weight: 2}
        ]
    }
}).addTo(map);

L.Routing.errorControl(control).addTo(map);