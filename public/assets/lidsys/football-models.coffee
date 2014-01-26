window.FootballScheduleService = class FootballScheduleService
    constructor: (@$http, @$q, @teamService) ->
        @seasons = null
        @weeks   = {}
        @games   = {}

        @selectedSeason = null
        @selectedWeek   = null



    setSelectedWeek: (selectedYear, selectedWeekNumber) ->
        @load selectedYear, selectedWeekNumber
    getSelectedSeason: -> @selectedSeason
    getSelectedWeek:   -> @selectedWeek



    load: (requestedYear, requestedWeek) ->
        year    = requestedYear
        week    = requestedWeek
        @$q.when(@loadSeasons())
            .then((response) =>
                seasons = @getSeasons()
                if not seasons[year]?
                    for own a_year of seasons
                        year = a_year

                @$q.when(@loadWeeks(year))
            )
            .then((response) =>
                today = moment().format('YYYY-MM-DD')
                weeks = @getWeeks(year)
                if not weeks[week]?
                    week = 0
                    for own week_num, a_week of weeks
                        week = week_num if a_week.start_date < today or not week

                if requestedYear isnt year or requestedWeek isnt week
                    @$q.reject({
                        year,
                        week
                    })
                else
                    @selectedSeason = @getSeason year
                    @selectedWeek   = @getWeek year, week
                    @$q.when(@loadGames(year, week))
            )


    loadSeasons: ->
        return @seasons if @seasons?
        @$http.get("/api/v1.0/football/seasons")
            .success((response) => @seasons = response.seasons)


    loadWeeks: (year) ->
        return @weeks[year] if @weeks[year]?
        @$http.get("/api/v1.0/football/weeks/#{year}")
            .success((response) => @weeks[year] = response.weeks)


    loadGames: (year, week) ->
        return @games[year][week] if @games[year]? and @games[year][week]?
        @$http.get("/api/v1.0/football/schedule/#{year}/#{week}")
            .success((response) =>
                teams = @teamService.getTeams()
                games = response.games
                for own game_id, game of games
                    game.away_team = teams[game.away_team_id]
                    game.home_team = teams[game.home_team_id]

                @games[year]       = {}
                @games[year][week] = games
            )


    getSeason: (year) ->
        @getSeasons()[year]


    getSeasons: ->
        throw "Seasons not yet loaded using 'loadSeasons'" if not @seasons?
        @seasons


    getWeek: (year, week_num) ->
        @getWeeks(year)[week_num]


    getWeeks: (year) ->
        throw "Weeks not yet loaded using 'loadWeeks' for year #{year}" if not @weeks[year]?
        @weeks[year]


    getWeeksArray: (year) ->
        throw "Weeks not yet loaded using 'loadWeeks' for year #{year}" if not @weeks[year]?
        for own week_num, week of @weeks[year]
            week


    getGames: (year, week_num) ->
        if not year?
            year     = @selectedSeason.year 
            week_num = null
        if not week_num?
            week_num = @selectedWeek.week_number 

        throw "Games not yet loaded using 'loadGames' for year #{year} week #{week_num}" if not @games[year]? or not @games[year][week_num]?
        @games[year][week_num]



window.FootballTeamService = class FootballTeamService
    constructor: (@$http, @$q) ->
        @teams   = null



    load: ->
        @$q.when(@loadTeams())


    loadTeams: ->
        return @teams if @teams?
        @$http.get("/api/v1.0/football/teams")
            .success((response) => @teams = response.teams)


    getTeams: ->
        throw "Teams not yet loaded using 'loadTeams'" if not @teams?
        @teams




window.FootballTeamStandingService = class FootballTeamService
    constructor: (@$http, @$q) ->
        @teamStandings   = {}

        @selectedConference = 'AFC'
        @selectedDivision   = 'North'


    setSelectedConference: (@selectedConference) ->
    getSelectedConference: -> @selectedConference
    setSelectedDivision:   (@selectedDivision) ->
    getSelectedDivision:   -> @selectedDivision


    load: (requestedYear, requestedWeek) ->
        @$q.when(@loadTeamStandings(requestedYear, requestedWeek))


    loadTeamStandings: (year, week) ->
        return @teamStandings[year][week] if @teamStandings[year]? and @teamStandings[year][week]?
        @$http.get("/api/v1.0/football/team-standings/#{year}/#{week}")
            .success((response) =>
                @teamStandings[year]       = {}
                @teamStandings[year][week] = response.team_standings
            )


    getTeamStandings: (year, week_num) ->
        throw "Team standings not yet loaded using 'loadTeamStandings' for year #{year} week #{week_num}" if not @teamStandings[year]? or not @teamStandings[year][week_num]?
        @teamStandings[year][week_num]




window.FootballPickService = class FootballPickService
    constructor: (@$http, @$q) ->
        @picks              = {}


    load: (requestedYear, requestedWeek) ->
        @$q.when(@loadPicks(requestedYear, requestedWeek))


    loadPicks: (year, week) ->
        return @picks[year][week] if @picks[year]? and @picks[year][week]?
        @$http.get("/api/v1.0/football/fantasy-picks/#{year}/#{week}")
            .success((response) =>
                @picks[year]       = {}
                @picks[year][week] = response.fantasy_picks
            )


    getPicks: (year, week_num) ->
        throw "Picks not yet loaded using 'loadPicks' for year #{year} week #{week_num}" if not @picks[year]? or not @picks[year][week_num]?
        @picks[year][week_num]




window.FootballFantasyPlayerService = class FootballFantasyPlayerService
    constructor: (@$http, @$q) ->
        @players              = {}


    load: (requestedYear, requestedWeek) ->
        @$q.when(@loadPlayers(requestedYear, requestedWeek))


    loadPlayers: (year) ->
        return @players[year] if @players[year]?
        @$http.get("/api/v1.0/football/fantasy-players/#{year}")
            .success((response) =>
                @players[year]       = response.fantasy_players
            )


    getPlayers: (year, week_num) ->
        throw "Players not yet loaded using 'loadPlayers' for year #{year}" if not @players[year]?
        @players[year]
