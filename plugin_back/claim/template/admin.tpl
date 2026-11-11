<section class="module-card">
	<header><i class="fa-regular fa-id-card"></i> Claim / Ownership</header>
	
	<!-- Statistiques -->
	<div class="claim-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
		<div class="stat-card" style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 1rem;">
			<div style="font-size: 2rem; font-weight: bold; color: #0369a1;">{{ stats.claimed_pois }}</div>
			<div style="color: #0c4a6e; font-size: 0.9rem;">POI revendiqués</div>
			{% if stats.total_pois > 0 %}
			<div style="color: #64748b; font-size: 0.8rem; margin-top: 0.25rem;">{{ stats.claimed_percentage }}% du total ({{ stats.total_pois }} POI)</div>
			{% endif %}
		</div>
		<div class="stat-card" style="background: #fef3c7; border: 1px solid #fde68a; border-radius: 8px; padding: 1rem;">
			<div style="font-size: 2rem; font-weight: bold; color: #d97706;">{{ stats.pending_claims }}</div>
			<div style="color: #92400e; font-size: 0.9rem;">Demandes en attente</div>
		</div>
		<div class="stat-card" style="background: #d1fae5; border: 1px solid #86efac; border-radius: 8px; padding: 1rem;">
			<div style="font-size: 2rem; font-weight: bold; color: #059669;">{{ stats.approved_claims }}</div>
			<div style="color: #047857; font-size: 0.9rem;">Demandes approuvées</div>
		</div>
		<div class="stat-card" style="background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; padding: 1rem;">
			<div style="font-size: 2rem; font-weight: bold; color: #dc2626;">{{ stats.rejected_claims }}</div>
			<div style="color: #991b1b; font-size: 0.9rem;">Demandes rejetées</div>
		</div>
		<div class="stat-card" style="background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 8px; padding: 1rem;">
			<div style="font-size: 2rem; font-weight: bold; color: #374151;">{{ stats.total_claims }}</div>
			<div style="color: #4b5563; font-size: 0.9rem;">Total des demandes</div>
		</div>
	</div>
	
	<p>
		<a class="button" href="/admin/osm/overrides" target="_self">Aller aux overrides (enrichissement)</a>
	</p>
	{% if claims %}
	<table class="table">
		<thead>
			<tr>
				<th>ID</th><th>Fiche</th><th>User</th><th>Status</th><th>Note</th><th>Actions</th><th>Justificatif</th>
			</tr>
		</thead>
		<tbody>
			{% for c in claims %}
				<tr>
					<td>{{ c.id }}</td>
					<td>
						{% if c.poi_name %}{{ c.poi_name }}{% else %}POI #{{ c.poi_id }}{% endif %}
					</td>
					<td>{{ c.display_user }}</td>
					<td>{{ c.status }}</td>
					<td>{{ c.note }}</td>
					<td>
						<form method="post" action="{{ ROUTER.generate("admin-claim-decide") }}">
							<input type="hidden" name="_csrf" value="{{ _csrfToken }}"/>
							<input type="hidden" name="id" value="{{ c.id }}"/>
							<button class="button success" name="decision" value="approved" type="submit">Approuver</button>
							<button class="button alert" name="decision" value="rejected" type="submit">Rejeter</button>
						</form>
					</td>
					<td>
						{% if c.proof_path %}
							<button class="button" type="button" 
							        data-proof-url="{{ ROUTER.generate("admin-claim-proof", ["id" => c.id]) }}"
							        data-proof-filename="{{ c.proof_filename }}">Voir la preuve</button>
						{% else %}
							N/A
						{% endif %}
					</td>
				</tr>
			{% endfor %}
		</tbody>
	</table>
	{% else %}
	<p>Aucune demande pour l’instant.</p>
	{% endif %}
</section>

<div id="claim-proof-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
	<div style="background:#fff; width:90%; max-width:960px; max-height:90%; border-radius:8px; overflow:hidden; display:flex; flex-direction:column;">
		<div style="padding:8px; display:flex; justify-content:space-between; align-items:center; background:#0f172a; color:#e2e8f0;">
			<span>Justificatif</span>
			<button id="claim-proof-close" class="button alert" type="button" style="padding:4px 8px;">Fermer</button>
		</div>
		<div style="flex:1; overflow:auto; display:flex; align-items:center; justify-content:center; padding:1rem;">
			<img id="claim-proof-image" src="" alt="Justificatif" style="max-width:100%; max-height:100%; object-fit:contain; display:none;">
			<iframe id="claim-proof-iframe" src="" width="100%" height="100%" style="border:0; min-height:400px; display:none;"></iframe>
			<object id="claim-proof-object" data="" type="application/pdf" width="100%" height="100%" style="border:0; min-height:400px; display:none;">
				<p>Impossible d'afficher le fichier. <a id="claim-proof-download" href="" download>Télécharger le fichier</a></p>
			</object>
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
	const modal = document.getElementById('claim-proof-modal');
	const imageViewer = document.getElementById('claim-proof-image');
	const iframeViewer = document.getElementById('claim-proof-iframe');
	const objectViewer = document.getElementById('claim-proof-object');
	const downloadLink = document.getElementById('claim-proof-download');
	const btnClose = document.getElementById('claim-proof-close');
	
	if (!modal) return;
	
	function showProof(url, filename) {
		if (!url) return;
		
		// Cacher tous les viewers
		if (imageViewer) imageViewer.style.display = 'none';
		if (iframeViewer) iframeViewer.style.display = 'none';
		if (objectViewer) objectViewer.style.display = 'none';
		
		// Déterminer le type de fichier par extension du nom de fichier
		let ext = '';
		if (filename) {
			ext = filename.split('.').pop().toLowerCase();
		} else {
			// Fallback : essayer depuis l'URL
			ext = url.split('.').pop().toLowerCase();
		}
		
		const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
		const pdfExt = 'pdf';
		
		if (imageExts.includes(ext)) {
			// Afficher l'image
			if (imageViewer) {
				imageViewer.src = url;
				imageViewer.style.display = 'block';
			}
		} else if (ext === pdfExt) {
			// Afficher le PDF dans un iframe
			if (iframeViewer) {
				iframeViewer.src = url;
				iframeViewer.style.display = 'block';
			} else if (objectViewer) {
				objectViewer.data = url;
				objectViewer.style.display = 'block';
			}
		} else {
			// Pour les autres types, utiliser l'object
			if (objectViewer) {
				objectViewer.data = url;
				objectViewer.style.display = 'block';
			}
		}
		
		// Mettre à jour le lien de téléchargement avec le bon nom de fichier
		if (downloadLink) {
			downloadLink.href = url;
			if (filename) {
				downloadLink.download = filename;
			} else {
				downloadLink.download = '';
			}
		}
		
		modal.style.display = 'flex';
	}
	
	function hideProof() {
		if (imageViewer) {
			imageViewer.src = '';
			imageViewer.style.display = 'none';
		}
		if (iframeViewer) {
			iframeViewer.src = '';
			iframeViewer.style.display = 'none';
		}
		if (objectViewer) {
			objectViewer.data = '';
			objectViewer.style.display = 'none';
		}
		modal.style.display = 'none';
	}
	
	document.querySelectorAll('[data-proof-url]').forEach((btn) => {
		btn.addEventListener('click', () => {
			const url = btn.getAttribute('data-proof-url');
			const filename = btn.getAttribute('data-proof-filename');
			showProof(url, filename);
		});
	});
	
	if (btnClose) {
		btnClose.addEventListener('click', hideProof);
	}
	
	modal.addEventListener('click', (e) => {
		if (e.target === modal) {
			hideProof();
		}
	});
});
</script>

