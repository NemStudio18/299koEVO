<section class="module-card BugTracker-list">
	<header>
		<i class="fa-solid fa-list"></i>
		Bugs signalés
	</header>
	
	<div class="BugTracker-tabs">
		<a href="#pending" class="tab-link active" data-tab="pending">En attente ({{ pendingCount }})</a>
		<a href="#in-progress" class="tab-link" data-tab="in-progress">Pris en charge ({{ inProgressCount }})</a>
		<a href="#fixed" class="tab-link" data-tab="fixed">Corrigés ({{ fixedCount }})</a>
	</div>
	
	<div class="tab-content active" id="tab-pending">
		{% if pendingCount > 0 %}
					{% FOR bug IN pending %}
				<div class="bug-item">
					<div class="bug-header">
						<h3><a href="{{ ROUTER.generate("BugTracker-view", ["id" => bug.id]) }}">{{ bug.title }}</a></h3>
						<span class="badge badge-{{ bug.statusClass }}">{{ bug.statusLabel }}</span>
					</div>
					<div class="bug-meta">
						<span><i class="fa-solid fa-calendar"></i> {{ Util.getDateHour(bug.created_at) }}</span>
						{% if bug.reporter_name %}
							<span><i class="fa-solid fa-user"></i> {{ bug.reporter_name }}</span>
						{% endif %}
					</div>
					<p class="bug-description">{{ bug.descriptionShort }}{% if bug.isLongDescription %}...{% endif %}</p>
				</div>
			{% ENDFOR %}
		{% else %}
			<p class="no-bugs">Aucun bug en attente.</p>
		{% endif %}
	</div>
	
	<div class="tab-content" id="tab-in-progress">
		{% if inProgressCount > 0 %}
					{% FOR bug IN inProgress %}
				<div class="bug-item">
					<div class="bug-header">
						<h3><a href="{{ ROUTER.generate("BugTracker-view", ["id" => bug.id]) }}">{{ bug.title }}</a></h3>
						<span class="badge badge-{{ bug.statusClass }}">{{ bug.statusLabel }}</span>
					</div>
					<div class="bug-meta">
						<span><i class="fa-solid fa-calendar"></i> {{ Util.getDateHour(bug.created_at) }}</span>
						{% if bug.reporter_name %}
							<span><i class="fa-solid fa-user"></i> {{ bug.reporter_name }}</span>
						{% endif %}
					</div>
					<p class="bug-description">{{ bug.descriptionShort }}{% if bug.isLongDescription %}...{% endif %}</p>
				</div>
			{% ENDFOR %}
		{% else %}
			<p class="no-bugs">Aucun bug pris en charge.</p>
		{% endif %}
	</div>
	
	<div class="tab-content" id="tab-fixed">
		{% if fixedCount > 0 %}
					{% FOR bug IN fixed %}
				<div class="bug-item">
					<div class="bug-header">
						<h3><a href="{{ ROUTER.generate("BugTracker-view", ["id" => bug.id]) }}">{{ bug.title }}</a></h3>
						<span class="badge badge-{{ bug.statusClass }}">{{ bug.statusLabel }}</span>
					</div>
					<div class="bug-meta">
						<span><i class="fa-solid fa-calendar"></i> {{ Util.getDateHour(bug.created_at) }}</span>
						{% if bug.reporter_name %}
							<span><i class="fa-solid fa-user"></i> {{ bug.reporter_name }}</span>
						{% endif %}
					</div>
					<p class="bug-description">{{ bug.descriptionShort }}{% if bug.isLongDescription %}...{% endif %}</p>
				</div>
			{% ENDFOR %}
		{% else %}
			<p class="no-bugs">Aucun bug corrigé.</p>
		{% endif %}
	</div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const tabLinks = document.querySelectorAll('.tab-link');
	const tabContents = document.querySelectorAll('.tab-content');
	
	tabLinks.forEach(link => {
		link.addEventListener('click', function(e) {
			e.preventDefault();
			const targetTab = this.getAttribute('data-tab');
			
			// Mettre à jour les liens
			tabLinks.forEach(l => l.classList.remove('active'));
			this.classList.add('active');
			
			// Mettre à jour le contenu
			tabContents.forEach(content => {
				content.classList.remove('active');
				if (content.id === 'tab-' + targetTab) {
					content.classList.add('active');
				}
			});
		});
	});
});
</script>

