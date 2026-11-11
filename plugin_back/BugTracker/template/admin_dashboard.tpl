<section class="module-card BugTracker-admin">
	<header>
		<i class="fa-solid fa-bug"></i>
		Gestion des bugs
	</header>
	
	{% if stats %}
	<div class="bug-stats">
		<div class="stat-card stat-total">
			<div class="stat-value">{{ stats.total }}</div>
			<div class="stat-label">Total</div>
		</div>
		<div class="stat-card stat-pending">
			<div class="stat-value">{{ stats.pending }}</div>
			<div class="stat-label">En attente</div>
		</div>
		<div class="stat-card stat-progress">
			<div class="stat-value">{{ stats.in_progress }}</div>
			<div class="stat-label">Pris en charge</div>
		</div>
		<div class="stat-card stat-fixed">
			<div class="stat-value">{{ stats.fixed }}</div>
			<div class="stat-label">Corrigés</div>
		</div>
		<div class="stat-card stat-rejected">
			<div class="stat-value">{{ stats.rejected }}</div>
			<div class="stat-label">Rejetés</div>
		</div>
	</div>
	{% endif %}
	
	<div class="bug-list-admin">
		<h3>Tous les bugs</h3>
		{% if count(allBugs) > 0 %}
			<table class="table">
				<thead>
					<tr>
						<th>ID</th>
						<th>Titre</th>
						<th>Statut</th>
						<th>Priorité</th>
						<th>Date</th>
						<th>Rapporteur</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					{% FOR bug IN allBugs %}
						<tr>
							<td>#{{ bug.shortId }}</td>
							<td>
								<a href="{{ ROUTER.generate("admin-BugTracker-view", ["id" => bug.id]) }}">
									{{ bug.titleShort }}{% if bug.isLongTitle %}...{% endif %}
								</a>
							</td>
							<td>
								<span class="badge badge-{{ bug.statusClass }}">{{ bug.statusLabel }}</span>
							</td>
							<td>
								<span class="badge badge-{{ bug.priorityClass }}">{{ bug.priorityLabel }}</span>
							</td>
							<td>{{ Util.getDateHour(bug.created_at) }}</td>
						<td>
							{% if bug.hasReporterId %}
								<i class="fa-solid fa-user-check"></i> #{{ bug.reporter_id }}: {{ bug.reporter_name }}
							{% elseif bug.reporter_name %}
								{{ bug.reporter_name }}
							{% else %}
								-
							{% endif %}
						</td>
							<td>
								<a href="{{ ROUTER.generate("admin-BugTracker-view", ["id" => bug.id]) }}" class="button small">
									<i class="fa-solid fa-eye"></i> Voir
								</a>
							</td>
						</tr>
					{% ENDFOR %}
				</tbody>
			</table>
		{% else %}
			<p>Aucun bug signalé.</p>
		{% endif %}
	</div>
</section>

