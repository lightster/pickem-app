window.ActiveService = class ActiveService
    constructor: ->
        @user = null


    setUser: (@user) ->
    getUser: -> @user

    isLoggedIn: -> !!(@user && @user.userId)



window.User = class User
    constructor: ->
        @clear()

    clear: ->
        @userId          = null
        @playerId        = null
        @username        = null
        @name            = null
        @backgroundColor = null
        @displayName     = null
        @permissions     = {}


    isAuthorized: (permission) ->
        if @permissions[permission]? and @permissions[permission] then true else false


    setFromApi: (options) ->
        return @clear() if options == null

        names     = options.name.split(' ')
        firstName = names[0] ? ''
        lastName  = names[1] ? ''

        @userId          = options.user_id
        @playerId        = options.player_id
        @username        = options.username
        @name            = options.name
        @backgroundColor = options.background_color
        @displayName     = firstName.substring(0, 1) +
            firstName.substring(firstName.length - 1) +
            lastName.substring(0, 1)
        @
