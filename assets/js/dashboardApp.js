'use strict';

angular
    .module('ImpPortalAngularApp', [
        'ngRoute', 'ngAnimate', 'ngMessages', 'ngCookies'
    ])
    .config(function($routeProvider) {
        $routeProvider
            .when('/dashboard', {
                templateUrl: hd_mpi_url+'views/dashboard-template.php',
                controller: 'DashboardCtrl'
            })
            .otherwise({
                redirectTo: '/dashboard'
            });
    });
