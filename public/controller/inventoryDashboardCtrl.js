app.controller('inventoryDashboard', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $scope.randomVal = function(val = null) {
        if(!val) {
            val = 200
        }
        var r = Math.round(Math.random() * val)
        return r
    }
    $scope.initDoughnut = function() {
        var units = $('.doughnut_chart')
        var canvas, ctx, data, n1, n2, cont, div

        for(x = 0;x < units.length;x++) {
            n1 = $scope.randomVal()
            n2 = $scope.randomVal()
            cont = $(units[x])

            // Generate info
            var wadah = $('<div></div>')
            wadah.addClass('pd-t5')
            cont.append(wadah)

            div = $('<div></div>')
            div.append($('<span class="inline-block w-20">To Do</span>'))
            div.append($('<b class="inline-block w-20">' + (n1 + n2) + ' Lines</b>'))
            wadah.append(div)
            
            div = $('<div></div>')
            div.append($('<span class="inline-block w-20">Completed</span>'))
            div.append($('<b class="inline-block w-20">' + (n1) + ' Lines</b>'))
            wadah.append(div)
            
            div = $('<div></div>')
            div.append($('<span class="inline-block w-20">Operator</span>'))
            div.append($('<b class="inline-block w-20">' + ($scope.randomVal(6)) + '</b>'))
            wadah.append(div)
            // Generate chart

            canvas = $('<canvas></canvas>')
            cont.prepend(canvas)
            canvas.css('width', '100%')
            canvas.css('max-width', '90mm')
            canvas.css('height', '55mm')
            ctx = canvas[0].getContext('2d')
            new Chart(ctx, {
                type : 'doughnut',
                options : {
                    title : {
                        display : true,
                        text : $(units[x]).attr('title')
                    }
                },
                data : {
                    datasets : [
                        {
                            data : [n1, n2],
                            backgroundColor : ['#cccccc', '#0e9aef']
                        }
                    ],
                    labels : ['Not Completed', 'Completed'],
                }
            })
        }
    }
    $scope.initDoughnut()

    $scope.initLine = function() {
        var data = [ 'Cycle Count Accuracy', 'Cycle Count Completed', 'Inventory Hold', 'Lost', 'Negative Shop Dedicated', 'Putaways', 'Replenishments', 'RFN Utilization', 'Shorted Orders', 'Will Call Completion Time']
        var ul = $('#list_chart')
        var li
        for(i in data) {
            li = $('<li class="list-group-item">' + data[i] + '</li>')
            li.append($('<b class="pull-right">' + $scope.randomVal() + '</b>'))
            ul.append(li)
        }

        var config = {
            type: 'line',
            data: {
                labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
                datasets: [{
                    label : 'A',
                    borderColor: 'blue',
                    data: [
                        $scope.randomVal(),
                        $scope.randomVal(),
                        $scope.randomVal(),
                        $scope.randomVal(),
                        $scope.randomVal(),
                        $scope.randomVal(),
                        $scope.randomVal()
                    ],
                    fill: false,
                }, {
                    label : 'B',
                    fill: false,
                    borderColor: 'black',
                    data: [
                        100,
                        100,
                        100,
                        100,
                        100,
                        100,
                        100
                    ],
                }]
            },
            options: {
                responsive: true,
                title: {
                    display: false,
                },
                tooltips: {
                    mode: 'index',
                    intersect: false,
                },
                hover: {
                    mode: 'nearest',
                    intersect: true
                },
                scales: {
                    xAxes: [{
                        display: true,
                        scaleLabel: {
                            display: false,
                            labelString: 'Month'
                        }
                    }],
                    yAxes: [{
                        display: false,
                    }]
                }
            }
        };

        var ctx = $('#overview_chart')
        new Chart(ctx[0], config)
    }
    $scope.initLine()
});
