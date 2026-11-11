{% if sondage.getId() %}
<div class="sondage-stats">
    <h3>{{ Lang.sondage.results }}</h3>
    <div class="stats-summary">
        <div class="stat-item">
            <span class="stat-label">{{ Lang.sondage.totalVotes }}:</span>
            <span class="stat-value">{{ sondage.getTotalVotes() }}</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">{{ Lang.sondage.status }}:</span>
            <span class="stat-value">{% if sondage.getActive() %}{{ Lang.core-active }}{% else %}{{ Lang.core-inactive }}{% endif %}</span>
        </div>
    </div>
    {% if sondage.getTotalVotes() > 0 %}
        <div class="options-stats">
            {% for stat in optionsStats %}
                <div class="option-stat-row">
                    <div class="option-stat-header">
                        <span class="option-stat-text">{{ stat.text }}</span>
                        <span class="option-stat-count">{{ stat.votes }} ({{ stat.percentage }}%)</span>
                    </div>
                    <div class="option-stat-bar">
                        <div class="option-stat-progress" style="width: {{ stat.percentage }}%"></div>
                    </div>
                </div>
            {% endfor %}
        </div>
    {% endif %}
</div>
{% endif %}

<form method="post" action="{{ saveUrl }}">
    {{ SHOW.tokenField }}
    <input type="hidden" name="id" value="{{ sondage.getId() }}" />
    
    <div class="form-section">
        <label for="title">{{ Lang.sondage.title }}</label>
        <input type="text" name="title" id="title" value="{{ sondage.getTitle() }}" required placeholder="{{ Lang.sondage.title-placeholder }}" />
    </div>
    
    <div class="form-section">
        <label>{{ Lang.sondage.options }}</label>
        <div id="options-container" class="options-container">
            {% if sondage.options %}
                {% for option in sondage.options %}
                    <div class="option-row">
                        <input type="text" name="options[]" value="{{ option }}" required placeholder="{{ Lang.sondage.option-placeholder }}" />
                        <button type="button" class="button alert small remove-option">{{ Lang.sondage.delete-option }}</button>
                    </div>
                {% endfor %}
            {% else %}
                <div class="option-row">
                    <input type="text" name="options[]" required placeholder="{{ Lang.sondage.option-placeholder }}" />
                    <button type="button" class="button alert small remove-option">{{ Lang.sondage.delete-option }}</button>
                </div>
                <div class="option-row">
                    <input type="text" name="options[]" required placeholder="{{ Lang.sondage.option-placeholder }}" />
                    <button type="button" class="button alert small remove-option">{{ Lang.sondage.delete-option }}</button>
                </div>
            {% endif %}
        </div>
        <button type="button" id="add-option" class="button secondary">{{ Lang.sondage.add-option }}</button>
    </div>
    
    <div class="form-section">
        <label for="closeDate">{{ Lang.sondage.closeDate }}</label>
        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <input type="date" name="closeDate" id="closeDate" value="{{ closeDateFormatted }}" />
            <input type="time" name="closeTime" id="closeTime" value="{{ closeTimeFormatted }}" />
            <button type="button" id="clear-close-date" class="button secondary small">{{ Lang.sondage.clear-close-date }}</button>
        </div>
        <small>{{ Lang.sondage.closeDate-help }}</small>
    </div>
    
    <div class="form-section">
        <p class="checkbox-field">
            <input type="checkbox" name="isMultipleChoice" id="multiple" value="1" {% if sondage.getIsMultipleChoice() %}checked{% endif %} />
            <label for="multiple">{{ Lang.sondage.multiple-choice }}</label>
        </p>
        <p class="checkbox-field">
            <input type="checkbox" name="allowOther" id="other" value="1" {% if sondage.getAllowOther() %}checked{% endif %} />
            <label for="other">{{ Lang.sondage.allow-other }}</label>
        </p>
    </div>
    
    <div class="form-section">
        <p class="checkbox-field">
            <input type="checkbox" name="active" id="active" {% if sondage.getActive() %}checked{% endif %} />
            <label for="active">{{ Lang.sondage.active }}</label>
            <small>{{ Lang.sondage.active-help }}</small>
        </p>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="button primary">{{ Lang.save }}</button>
        <a href="{{ listUrl }}" class="button">{{ Lang.cancel }}</a>
    </div>
</form>

<style>
.sondage-stats {
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.sondage-stats h3 {
    margin: 0 0 1rem 0;
    color: #1a3a5f;
}

.stats-summary {
    display: flex;
    gap: 2rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e0e0e0;
}

.stat-item {
    display: flex;
    flex-direction: column;
}

.stat-label {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1a3a5f;
}

.options-stats {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.option-stat-row {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.option-stat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.option-stat-text {
    font-weight: 500;
    color: #333;
}

.option-stat-count {
    font-size: 0.9rem;
    color: #666;
}

.option-stat-bar {
    height: 24px;
    background: #f0f0f0;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
}

.option-stat-progress {
    height: 100%;
    background: linear-gradient(135deg, #6b3fa0 0%, #8b5fc0 100%);
    border-radius: 12px;
    transition: width 0.6s ease;
}

.form-section {
    margin-bottom: 2rem;
}

.form-section label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #1a3a5f;
}

.form-section input[type="text"] {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    font-size: 1rem;
}

.options-container {
    margin-bottom: 1rem;
}

.option-row {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    align-items: center;
}

.option-row input[type="text"] {
    flex: 1;
}

.checkbox-field {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.checkbox-field label {
    margin: 0;
    font-weight: normal;
}

.checkbox-field small {
    display: block;
    color: #666;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e0e0e0;
}

.button.secondary {
    background: #6c757d;
}

.button.secondary:hover {
    background: #5a6268;
}

.button.primary {
    background: #007bff;
}

.button.primary:hover {
    background: #0056b3;
}
</style>

<script>
document.getElementById('add-option')?.addEventListener('click', function() {
    const container = document.getElementById('options-container');
    const row = document.createElement('div');
    row.className = 'option-row';
    row.innerHTML = '<input type="text" name="options[]" required placeholder="{{ Lang.sondage.option-placeholder }}" /> <button type="button" class="button alert small remove-option">Supprimer</button>';
    container.appendChild(row);
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-option')) {
        const container = document.getElementById('options-container');
        if (container.children.length > 1) {
            e.target.closest('.option-row').remove();
        }
    }
    if (e.target.id === 'clear-close-date') {
        document.getElementById('closeDate').value = '';
        document.getElementById('closeTime').value = '23:59';
    }
});
</script>
