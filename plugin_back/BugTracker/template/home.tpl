<section class="module-card BugTracker-home">
	<header>
		<i class="fa-solid fa-bug"></i>
		Rapport de bug
	</header>
	
	<div class="BugTracker-intro">
		<p>Vous avez rencontré un problème sur le site ? Aidez-nous à l'améliorer en signalant le bug que vous avez découvert.</p>
	</div>
	
	<div class="BugTracker-actions">
		<a href="{{ ROUTER.generate("BugTracker-report") }}" class="button primary">
			<i class="fa-solid fa-plus"></i> Signaler un bug
		</a>
		<a href="{{ ROUTER.generate("BugTracker-list") }}" class="button secondary">
			<i class="fa-solid fa-list"></i> Voir les bugs signalés
		</a>
	</div>
	
	{% if stats %}
	<div class="BugTracker-stats">
		<h3>Statistiques</h3>
		<div class="stats-grid">
			<div class="stat-item">
				<span class="stat-value">{{ stats.total }}</span>
				<span class="stat-label">Total</span>
			</div>
			<div class="stat-item stat-pending">
				<span class="stat-value">{{ stats.pending }}</span>
				<span class="stat-label">En attente</span>
			</div>
			<div class="stat-item stat-progress">
				<span class="stat-value">{{ stats.in_progress }}</span>
				<span class="stat-label">Pris en charge</span>
			</div>
			<div class="stat-item stat-fixed">
				<span class="stat-value">{{ stats.fixed }}</span>
				<span class="stat-label">Corrigés</span>
			</div>
		</div>
	</div>
	{% endif %}
</section>

