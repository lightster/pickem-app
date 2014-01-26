var module =  angular.module('ldsFootball', ['ngRoute']);

module.config(['$injector', '$routeProvider', function ($injector, $routeProvider) {
    $routeProvider
        .when('/football/my-picks/:year?/:week?',
        {
            templateUrl: "/app/template/football/my-picks.html",
            controller: "LidsysFootballPicksCtrl",
            resolve: $injector.get('lidsysFootballPicksRouteResolver'),
            navigationLabel: "My Picks",
            isFootball: true
        })
        .when('/football/league-picks/:year?/:week?',
        {
            templateUrl: "/app/template/football/league-picks.html",
            controller: "LidsysFootballLeaguePicksCtrl",
            resolve: $injector.get('lidsysFootballPicksRouteResolver'),
            navigationLabel: "League Picks",
            isFootball: true
        })
        .when('/football/schedule/:year?/:week?',
        {
            templateUrl: "/app/template/football/schedule.html",
            controller: "LidsysFootballScheduleCtrl",
            resolve: $injector.get('lidsysFootballWeekSensitiveRouteResolver'),
            navigationLabel: "Schedule",
            isFootball: true
        })
        .when('/football/team-standings/:year?/:week?',
        {
            templateUrl: "/app/template/football/team-standings.html",
            controller: "LidsysFootballTeamStandingsCtrl",
            resolve: [['$injector', '$route', '$q', 'lidsysFootballSchedule', 'lidsysFootballTeamStanding', function($injector, $route, $q, footballSchedule, footballTeamStanding) {
                var resolvers = $injector.get('lidsysFootballWeekSensitiveRouteResolver');
                return $q.all({
                    resolveValidWeek: $injector.invoke(resolvers.resolveValidWeek),
                    resolveTeams:     $injector.invoke(resolvers.resolveTeams)
                }).then(function () {
                    return footballTeamStanding.load(
                        footballSchedule.getSelectedSeason().year,
                        footballSchedule.getSelectedWeek().week_number
                    )
                })
            }]],
            navigationLabel: "Team Standings",
            isFootball: true
        })
}])

module.constant('lidsysFootballWeekSensitiveRouteResolver', {
    resolveValidWeek: ['$location', '$q', '$route', 'lidsysFootballSchedule', function ($location, $q, $route, footballSchedule) {
        var year = $route.current.params.year,
            week = $route.current.params.week
        return footballSchedule.load(year, week)
            .catch(function (message) {
                if (message.year && message.week) {
                    $location.path(
                        $route.current.originalPath
                            .replace(":year?", message.year)
                            .replace(":week?", message.week)
                    ).replace()
                }

                return $q.reject(message)
            })
    }],
    resolveTeams: ['lidsysFootballTeam', function (footballTeam) {
        return footballTeam.load()
    }]
})

module.constant('lidsysFootballPicksRouteResolver', {
    resolvePicks: ['$injector', '$route', '$q', 'lidsysFootballFantasyPlayer','lidsysFootballPick',  'lidsysFootballSchedule', function($injector, $route, $q, footballFantasyPlayer, footballPick, footballSchedule) {
        var resolvers = $injector.get('lidsysFootballWeekSensitiveRouteResolver');
        return $q.all({
            resolveValidWeek: $injector.invoke(resolvers.resolveValidWeek),
            resolveTeams:     $injector.invoke(resolvers.resolveTeams)
        }).then(function () {
            return footballPick.load(
                footballSchedule.getSelectedSeason().year,
                footballSchedule.getSelectedWeek().week_number
            )
        }).then(function () {
            return footballFantasyPlayer.load(
                footballSchedule.getSelectedSeason().year
            )
        })
    }]
})

module.factory('lidsysFootballFantasyPlayer', ['$http', '$q', function($http, $q) {
    return new FootballFantasyPlayerService($http, $q)
}])

module.factory('lidsysFootballPick', ['$http', '$q', function($http, $q) {
    return new FootballPickService($http, $q)
}])

module.factory('lidsysFootballSchedule', ['$http', '$q', 'lidsysFootballTeam', function($http, $q, footballTeam) {
    return new FootballScheduleService($http, $q, footballTeam)
}])

module.factory('lidsysFootballTeam', ['$http', '$q', function($http, $q) {
    return new FootballTeamService($http, $q)
}])

module.factory('lidsysFootballTeamStanding', ['$http', '$q', function($http, $q) {
    return new FootballTeamStandingService($http, $q)
}])

module.directive('ldsFootballWeekSelector', [function () {
    return {
        restrict: "E",
        controller: ['$location', '$route', '$scope', 'lidsysFootballSchedule', function ($location,  $route, $scope, footballSchedule) {
            var season = footballSchedule.getSelectedSeason(),
                week   = footballSchedule.getSelectedWeek()
            $scope.week_selector = {
                season:  season,
                week:    week,
                seasons: footballSchedule.getSeasons(),
                weeks:   footballSchedule.getWeeksArray(season.year)
            };
            $scope.changeSelectedWeek = function() {
                $location.path(
                    $route.current.originalPath
                        .replace(":year?", $scope.week_selector.season.year)
                        .replace(":week?", $scope.week_selector.week.week_number)
                )
            }
        }],
        templateUrl: "/app/template/football/week-selector.html"
    }
}])

module.controller('LidsysFootballPicksCtrl', ['$scope', 'lidsysFootballFantasyPlayer', 'lidsysFootballPick', 'lidsysFootballSchedule', 'lidsysFootballTeam', function ($scope, footballPlayer, footballPick, footballSchedule, footballTeam) {
    var season  = footballSchedule.getSelectedSeason(),
        week    = footballSchedule.getSelectedWeek(),
        picks   = footballPick.getPicks(season.year, week.week_number),
        teams   = footballTeam.getTeams(),
        games   = footballSchedule.getGames(),
        players = footballPlayer.getPlayers(season.year),
        game    = null,
        game_id = null
    for (game_id in games) {
        game = games[game_id]

        game.picks = picks[game.game_id]
    }
    $scope.currentPlayerId = 6
    $scope.currentPlayer   = players[$scope.currentPlayerId]
    $scope.games           = games
    $scope.prevGameTime    = null
    $scope.headerExists = function (game) {
        if ($scope.prevGameTime === game.start_time) {
            return false
        }

        $scope.prevGameTime = game.start_time
        return true
    }
    $scope.getPickedTeamStyle = function (game, team) {
        if (game.picks[$scope.currentPlayer.player_id].team_id == team.team_id) {
            return {'background-color': '#' + $scope.currentPlayer.background_color}
        }

        return ""
    }
}])

module.controller('LidsysFootballLeaguePicksCtrl', ['$scope', 'lidsysFootballPick', 'lidsysFootballSchedule', 'lidsysFootballTeam', function ($scope, footballPick, footballSchedule, footballTeam) {
    var season  = footballSchedule.getSelectedSeason(),
        week    = footballSchedule.getSelectedWeek(),
        picks   = footballPick.getPicks(season.year, week.week_number),
        teams   = footballTeam.getTeams(),
        games   = footballSchedule.getGames(),
        game    = null,
        game_id = null
    for (game_id in games) {
        game = games[game_id]

        game.picks = picks[game.game_id]
    }
    $scope.currentPlayerId  = 6
    $scope.games            = games
    $scope.prevGame         = null
    $scope.prevHeaderExists = null
    $scope.prevGameTime     = null
    $scope.headerExists = function (game) {
        console.log($scope.prevGame)
        if ($scope.prevGame === game) {
            console.log(game)
            return $scope.prevHeaderExists
        }

        $scope.prevHeaderExists = ($scope.prevGameTime !== game.start_time)
        $scope.prevGameTime     = game.start_time
        $scope.prevGame         = game

        return $scope.prevHeaderExists
    }
}])

module.controller('LidsysFootballScheduleCtrl', ['$scope', 'lidsysFootballSchedule', 'lidsysFootballTeam', function ($scope, footballSchedule, footballTeam) {
    var teams   = footballTeam.getTeams(),
        games   = footballSchedule.getGames(),
        game    = null,
        game_id = null
    $scope.games        = games
    $scope.prevGameTime = null
    $scope.headerExists = function (game) {
        if ($scope.prevGameTime === game.start_time) {
            return false
        }

        $scope.prevGameTime = game.start_time
        return true
    }
}])

module.directive('ldsFootballDivisionSelector', [function () {
    return {
        restrict: "E",
        controller: ['$location', '$route', '$scope', 'lidsysFootballTeamStanding', function ($location,  $route, $scope, footballTeamStanding) {
            // var conference = footballTeamStanding.getSelectedConference(),
            //     division   = footballTeamStanding.getSelectedDivision()
            $scope.getSelectedConference = function () {
                return footballTeamStanding.getSelectedConference()
            }
            $scope.setSelectedConference = function ($event, conference) {
                $event.preventDefault()
                footballTeamStanding.setSelectedConference(conference)
                $route.reload()
            }
            $scope.getSelectedDivision   = function () {
                return footballTeamStanding.getSelectedDivision()
            }
            $scope.setSelectedDivision   = function ($event, division) {
                $event.preventDefault()
                footballTeamStanding.setSelectedDivision(division)
                $route.reload()
            }
        }],
        templateUrl: "/app/template/football/division-selector.html"
    }
}])

module.controller('LidsysFootballTeamStandingsCtrl', ['$scope', 'lidsysFootballSchedule', 'lidsysFootballTeam', 'lidsysFootballTeamStanding', function ($scope, footballSchedule, footballTeam, footballTeamStanding) {
    var season = footballSchedule.getSelectedSeason(),
        week   = footballSchedule.getSelectedWeek(),
        conference = footballTeamStanding.getSelectedConference(),
        division   = footballTeamStanding.getSelectedDivision()
    var teams               = footballTeam.getTeams(),
        standings           = footballTeamStanding.getTeamStandings(
            season.year,
            week.week_number
        ),
        standing_idx,
        standing,
        team,
        filteredStandings = []
    for (standing_idx in standings) {
        standing = standings[standing_idx]
        if (standing.team_id) {
            team          = teams[standing.team_id]
            standing.team = team
            if (team.conference == conference && team.division == division) {
                filteredStandings.push(standing)
            }
        }
    }
    $scope.standings = filteredStandings
}])