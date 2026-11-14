<section>
    <header>{% if question.getId() %}{{ Lang.faq-admin.edit-title }}{% else %}{{ Lang.faq-admin.add-title }}{% endif %}</header>
    
    <form method="post" action="{{ saveUrl }}">
        {{ SHOW.tokenField }}
        <input type="hidden" name="id" value="{% if question.getId() %}{{ question.getId() }}{% endif %}" />
        
        <label for="question">{{ Lang.faq-admin.question }}</label>
        <input type="text" name="question" id="question" value="{{ question.getQuestion() }}" required />
        
        <label for="answer">{{ Lang.faq-admin.answer }}</label>
        <textarea name="answer" id="answer" rows="10" class="editor" required>{% HOOK.beforeEditEditor(question.getAnswer()) %}</textarea>
        
        <label for="category">{{ Lang.faq-admin.category }}</label>
        <input type="text" name="category" id="category" value="{{ question.getCategory() }}" list="categories-list" />
        <datalist id="categories-list">
            {% for cat in categories %}
                <option value="{{ cat }}">
            {% endfor %}
        </datalist>
        
        <label for="order">{{ Lang.faq-admin.order }}</label>
        <input type="number" name="order" id="order" value="{{ question.getOrder() }}" />
        
        <p>
            <input type="checkbox" name="active" id="active"{% if question.isActive() %} checked{% endif %} />
            <label for="active">{{ Lang.faq-admin.active }}</label>
        </p>
        
        <button type="submit" class="button">{{ Lang.save }}</button>
    </form>
</section>



