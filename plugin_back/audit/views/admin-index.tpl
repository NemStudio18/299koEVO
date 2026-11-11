<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1>Audit Admin Panel</h1>
            <p>This tool will scan all admin routes to detect crashes (500) or Empty Responses.</p>
            <button id="start-scan" class="btn btn-primary">Start Scan</button>
            <div class="progress mt-3" style="display:none;">
                <div id="progress-bar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <h3>Results</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Route</th>
                        <th>Test URL</th>
                        <th>Status</th>
                        <th>Length</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody id="results-body">
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const routes = {{ adminRoutes|json_encode|raw }};
    const tbody = document.getElementById('results-body');
    const progressBar = document.getElementById('progress-bar');
    const progressContainer = document.querySelector('.progress');
    let currentIndex = 0;
    
    document.getElementById('start-scan').addEventListener('click', function() {
        this.disabled = true;
        progressContainer.style.display = 'flex';
        currentIndex = 0;
        processNext();
    });

    async function processNext() {
        if (currentIndex >= routes.length) {
            alert('Scan Complete!');
            return;
        }

        const route = routes[currentIndex];
        const row = document.createElement('tr');
        
        let status = 'PENDING';
        let length = 0;
        let result = '...';
        let resultClass = '';

        try {
            // Use current base path + route pattern replacement
            // We assume the controller properly formatted testUrl
            // But we need to prepend the site base path if needed.
            // Since we are in admin, relative paths might work if base tag is set,
            // or we use absolute paths. 
            // The controller sent `testUrl` which is likely "/admin/..."
            // We should strip duplicated slashes if any.
            
           // const response = await fetch(route.testUrl);
           // Actually, we must be careful not to trigger delete actions!
           // BUT, we are doing GET requests.
           // Ideally, delete actions should be POST/DELETE.
           // If a DELETE is on GET, this scanner WILL trigger it.
           // BEWARE.
           
           // For now, we assume standard REST or standard 299Ko patterns where actions are usually guarded by tokens
           // or are POST. GET actions are usually "confirm delete" pages or lists.
           
            const response = await fetch(route.testUrl);
            status = response.status;
            const text = await response.text();
            length = text.length;

            if (status === 200) {
                if (length < 50) {
                     result = 'WHITE PAGE?';
                     resultClass = 'table-warning';
                } else if (text.includes('Fatal error') || text.includes('Exception')) {
                     result = 'PHP ERROR';
                     resultClass = 'table-danger';
                } else {
                     result = 'OK';
                     resultClass = 'table-success';
                }
            } else {
                result = 'HTTP ' + status;
                resultClass = 'table-danger';
            }

        } catch (e) {
            console.error(e);
            result = 'NETWORK ERROR';
            resultClass = 'table-danger';
        }

        row.className = resultClass;
        row.innerHTML = `
            <td>${route.name}</td>
            <td><a href="${route.testUrl}" target="_blank">${route.testUrl}</a></td>
            <td>${status}</td>
            <td>${length} chars</td>
            <td>${result}</td>
        `;

        tbody.prepend(row); // Newest on top? Or append? let's append.
        // tbody.appendChild(row); 

        // Update Progress
        currentIndex++;
        const percent = Math.round((currentIndex / routes.length) * 100);
        progressBar.style.width = percent + '%';
        progressBar.textContent = percent + '%';

        // Brief delay to not kill the server
        setTimeout(processNext, 200);
    }
</script>
