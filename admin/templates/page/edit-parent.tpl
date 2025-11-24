<section>
    <header>{{ Lang.page.edit-parent }}</header>
    <form method="post" action="{{ ROUTER.generate("page-admin-save") }}" enctype="multipart/form-data">
        {{ show::tokenField() }}
        <input type="hidden" name="id" value="{{ pageItem.getId() }}" />
        <!--<input type="hidden" name="position" value="{{ pageItem.getPosition() }}" />-->
        <input type="hidden" name="target" value="javascript:" />
        <p>
            <input {% if pageItem.getIsHidden() %}checked{% endif %} type="checkbox" name="isHidden" id="isHidden" />
            <label for="isHidden">{{ Lang.page.hide-from-menu }}</label>
        </p>
        <p>
            <label for="name">{{ Lang.page.name-label }}</label><br>
            <input type="text" name="name" id="name" value="{{ pageItem.getName() }}" required="required" />
        </p>
        <p>
            <label for="cssClass">{{ Lang.page.css-class }}</label>
            <input type="text" name="cssClass" id="cssClass" value="{{ pageItem.getCssClass() }}" />
        </p>
        <p>
            <label for="position">{{ Lang.page.position }}</label>
            <input type="number" name="position" id="position" value="{{ pageItem.getPosition() }}" />
        </p>
        <p>
            <button type="submit" class="button success radius">{{ Lang.submit }}</button>
        </p>
    </form>
</section>
