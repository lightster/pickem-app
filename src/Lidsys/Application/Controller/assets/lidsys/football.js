var module =  angular.module('ldsFootball', ['ngRoute']);

module.config(['$injector', '$routeProvider', function ($injector, $routeProvider) {
    $routeProvider
        .when('/football/my-picks/:year?/:week?',
        {
            templateUrl: "/app/template/football/my-picks.html",
            controller: "LidsysFootballPicksCtrl",
            resolve: [['$injector', '$route', '$q', 'lidsysFootballSchedule', 'lidsysFootballTeamStanding', function($injector, $route, $q, footballSchedule, footballTeamStanding) {
                var resolvers = $injector.get('lidsysFootballPicksRouteResolver');
                return $q.all({
                    resolvePicks: $injector.invoke(resolvers.resolvePicks)
                }).then(function () {
                    return footballTeamStanding.load(
                        footballSchedule.getSelectedSeason().year,
                        footballSchedule.getSelectedWeek().week_number
                    )
                })
            }]],
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
        .when('/football/fantasy-standings/:year?/:week?',
        {
            templateUrl: "/app/template/football/fantasy-standings.html",
            controller: "LidsysFootballFantasyStandingsCtrl",
            resolve: $injector.get('lidsysFootballFantasyStandingsRouteResolver'),
            navigationLabel: "Fantasy Standings",
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

module.constant('lidsysFootballFantasyStandingsRouteResolver', {
    resolveFantasyStandings: ['$injector', '$route', '$q', 'lidsysFootballFantasyPlayer','lidsysFootballFantasyStanding',  'lidsysFootballSchedule', function($injector, $route, $q, footballFantasyPlayer, footballFantasyStanding, footballSchedule) {
        var resolvers = $injector.get('lidsysFootballWeekSensitiveRouteResolver');
        return $q.all({
            resolveValidWeek: $injector.invoke(resolvers.resolveValidWeek),
            resolveTeams:     $injector.invoke(resolvers.resolveTeams)
        }).then(function () {
            return footballFantasyPlayer.load(
                footballSchedule.getSelectedSeason().year
            )
        }).then(function () {
            return footballFantasyStanding.load(
                footballSchedule.getSelectedSeason().year
            )
        })
    }]
})

module.factory('lidsysFootballFantasyPlayer', ['$http', '$q', function($http, $q) {
    return new FootballFantasyPlayerService($http, $q)
}])

module.factory('lidsysFootballFantasyStanding', ['$http', '$q', function($http, $q) {
    return new FootballFantasyStandingService($http, $q)
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

module.controller('LidsysFootballPicksCtrl', ['$scope', 'lidsysFootballFantasyPlayer', 'lidsysFootballPick', 'lidsysFootballSchedule', 'lidsysFootballTeam', 'lidsysFootballTeamStanding', function ($scope, footballPlayer, footballPick, footballSchedule, footballTeam, footballTeamStanding) {
    var season  = footballSchedule.getSelectedSeason(),
        week    = footballSchedule.getSelectedWeek(),
        picks   = footballPick.getPicks(season.year, week.week_number),
        teams   = footballTeam.getTeams(),
        games   = footballSchedule.getGames(),
        players = footballPlayer.getPlayers(season.year),
        game    = null,
        game_id = null,
        standing_id    = null,
        standings      = {},
        team_standing  = null,
        team_standings = footballTeamStanding.getTeamStandings(
            season.year,
            week.week_number
        )
    for (game_id in games) {
        game = games[game_id]

        game.picks = picks[game.game_id]
    }
    console.log(team_standings)
    for (standing_id in team_standings) {
        team_standing = team_standings[standing_id]
        standings[team_standing.team_id] = team_standing
    }

    $scope.currentPlayerId = 6
    $scope.week            = week
    $scope.currentPlayer   = players[$scope.currentPlayerId]
    $scope.games           = games
    $scope.prevGameTime    = null
    $scope.standings        = standings
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

module.controller('LidsysFootballLeaguePicksCtrl', ['$scope', 'lidsysFootballPick', 'lidsysFootballFantasyPlayer', 'lidsysFootballSchedule', 'lidsysFootballTeam', function ($scope, footballPick, footballPlayer, footballSchedule, footballTeam) {
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

    $scope.currentPlayerId  = 6
    $scope.week             = week
    $scope.games            = games
    $scope.players          = players
    $scope.playerCount      = 0
    $scope.prevGame         = null
    $scope.prevHeaderExists = null
    $scope.prevGameTime     = null

    var playerCount = 0
    for (var player_id in players) {
        var player = players[player_id]
        if (player.player_id) {
            ++playerCount
        }
    }
    $scope.playerCount = playerCount

    $scope.headerExists = function (game) {
        if ($scope.prevGame === game) {
            return $scope.prevHeaderExists
        }

        $scope.prevHeaderExists = ($scope.prevGameTime !== game.start_time)
        $scope.prevGameTime     = game.start_time
        $scope.prevGame         = game

        return $scope.prevHeaderExists
    }
    $scope.getPickedTeamStyle = function (pick, team) {
        if (pick.team_id == team.team_id) {
            return {
                'background-color': '#' + $scope.players[pick.player_id].background_color,
                'color': '#ffffff'
            }
        }
        else {
            return {
                'color': '#' + $scope.players[pick.player_id].background_color
            }
        }
    }
}])

module.controller('LidsysFootballFantasyStandingsCtrl', ['$scope', 'lidsysFootballFantasyPlayer', 'lidsysFootballSchedule', 'lidsysFootballFantasyStanding', function ($scope, footballPlayer, footballSchedule, footballFantasyStanding) {
    var season           = footballSchedule.getSelectedSeason(),
        selected_week    = footballSchedule.getSelectedWeek(),
        all_weeks        = footballSchedule.getWeeks(season.year),
        standings        = footballFantasyStanding.getStandings(season.year),
        players          = footballPlayer.getPlayers(season.year),
        weeks            = [],
        player_standings = [],
        rank             = 0,
        minPointsPerWeek = {},
        maxPointsPerWeek = {}
    for (var week_num in all_weeks) {
        var week = all_weeks[week_num]
        weeks.push({
            week: week,
            week_num: week_num
        })

        if (week == selected_week) {
            break
        }
    }

    for (var player_idx in players) {
        var player = players[player_idx],
            player_standing = {
                player:       player,
                standings:    [],
                total_points: 0,
                rank:         0,
                weeks_won:    0
            }
        for (var week_idx in weeks) {
            var week     = weeks[week_idx],
                standing = standings[week.week_num][player.player_id]

            if (standing) {
                player_standing.total_points += parseInt(standing.points)
                player_standing.standings.push({
                    standing: standing,
                    week:     week
                })

                var standing_points = parseInt(standing.points)

                if (!minPointsPerWeek[week.week_num]) {
                    minPointsPerWeek[week.week_num] = standing.points
                }
                else {
                    minPointsPerWeek[week.week_num]
                        = Math.min(standing.points, minPointsPerWeek[week.week_num])
                }

                if (!maxPointsPerWeek[week.week_num]) {
                    maxPointsPerWeek[week.week_num] = standing.points
                }
                else {
                    maxPointsPerWeek[week.week_num]
                        = Math.max(standing.points, maxPointsPerWeek[week.week_num])
                }
            }
            else {
                player_standing.standings.push({
                    standing: {},
                    week:     week
                })
            }
        }

        player_standings.push(player_standing)
    }
    player_standings.sort(function (a, b) {
        return b.total_points - a.total_points
    })
    var lastPoints = 0
    for (var player_standing_idx in player_standings) {
        var player_standing = player_standings[player_standing_idx]
        if (lastPoints != player_standing.total_points) {
            rank++
        }
        player_standings[player_standing_idx].rank = rank
        lastPoints = player_standing.total_points
    }

    for (var ps_idx = 0; ps_idx < player_standings.length; ps_idx++) {
        var player_standing = player_standings[ps_idx],
            standing_count  = player_standing.standings.length
        for (var standing_idx = 0; standing_idx < standing_count; standing_idx++) {
            var standing = player_standing.standings[standing_idx]
            if (standing.standing.points == maxPointsPerWeek[standing.week.week_num]) {
                player_standing.weeks_won++
            }
        }
    }

    $scope.currentPlayerId  = 6
    $scope.week             = selected_week
    $scope.weeks            = weeks
    $scope.players          = players
    $scope.standings        = player_standings
    $scope.minPointsPerWeek = minPointsPerWeek
    $scope.maxPointsPerWeek = maxPointsPerWeek

    $scope.getDisplayNameStyle = function (player) {
        return {
            'background-color': '#' + player.background_color,
            'color': '#ffffff'
        }
    }
    $scope.getWeekPointsStyle = function (player_standing) {
        var fontColor = 192
            week      = player_standing.week,
            standing  = player_standing.standing,
            minPoints = minPointsPerWeek[week.week_num],
            maxPoints = maxPointsPerWeek[week.week_num] 
        if(maxPoints - minPoints == 0) {
            fontColor = 0
        } else if (standing) {
            fontColor = parseInt(Math.max(
                0,
                parseInt(
                    fontColor
                    * (1 - (
                        (standing.points - minPoints)
                        / (maxPoints - minPoints)
                    ))
                )
            ))
        }

        var style = {
            'color': 'rgb('
                + fontColor
                + ','
                + fontColor
                + ','
                + fontColor
                + ')'
        }

        if (maxPoints != minPoints && maxPoints == standing.points) {
            style['background-color'] = '#ffff99'
        }

        return style
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