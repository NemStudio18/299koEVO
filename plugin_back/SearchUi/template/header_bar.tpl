<div class="search-pos-header">
    <form action="{ROUTER.generate('SearchUi-home')}" method="GET" class="header-search-form">
        <button type="submit" aria-label="{{Lang.search-submit}}">
            <i class="fa fa-search"></i>
        </button>
        <input type="text" name="query" value="{{query}}" placeholder="{{Lang.search-placeholder}}" autocomplete="off">
    </form>
</div>
