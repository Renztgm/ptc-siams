<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View All Admissions - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        
        .header {
            background-color: #1b5e20;
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header img {
            height: 50px;
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .filters {
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filters select, .filters input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 14px;
        }
        
        .filters button {
            padding: 8px 15px;
            background-color: #2e7d32;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .filters button:hover {
            background-color: #1b5e20;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            background: white;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-box h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-box .number {
            font-size: 32px;
            color: #2e7d32;
            font-weight: bold;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 5px;
            overflow: hidden;
        }
        
        thead {
            background-color: #1b5e20;
            color: white;
        }
        
        th {
            padding: 12px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #ddd;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        tbody tr:hover {
            background-color: #f9f9f9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-admitted {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .email-sent {
            color: #28a745;
            font-weight: bold;
        }
        
        .email-not-sent {
            color: #dc3545;
            font-weight: bold;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            font-size: 18px;
            color: #666;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="assets/Logo.png" alt="PTC Logo">
        <h1>All Admissions - Admin Panel</h1>
    </div>
    
    <div class="container">
        <div class="filters">
            <select id="statusFilter" onchange="loadAdmissions()">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="admitted">Admitted</option>
                <option value="rejected">Rejected</option>
            </select>
            
            <select id="programFilter" onchange="loadAdmissions()">
                <option value="">All Programs</option>
                <option value="BS Information Technology">BS Information Technology</option>
                <option value="BS Tourism Management">BS Tourism Management</option>
                <option value="BS Secondary Education">BS Secondary Education</option>
                <option value="TVL">TVL</option>
            </select>
            
            <button onclick="loadAdmissions()">Refresh</button>
        </div>
        
        <div class="stats">
            <div class="stat-box">
                <h3>Total Admissions</h3>
                <div class="number" id="totalCount">0</div>
            </div>
            <div class="stat-box">
                <h3>Pending</h3>
                <div class="number" id="pendingCount">0</div>
            </div>
            <div class="stat-box">
                <h3>Admitted</h3>
                <div class="number" id="admittedCount">0</div>
            </div>
            <div class="stat-box">
                <h3>Rejected</h3>
                <div class="number" id="rejectedCount">0</div>
            </div>
        </div>
        
        <div id="message"></div>
        <div id="loading" class="loading" style="display: none;">Loading admissions...</div>
        
        <table style="display: none;" id="admissionsTable">
            <thead>
                <tr>
                    <th>Admission ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Program</th>
                    <th>Status</th>
                    <th>Submission Date</th>
                    <th>Email Confirmation</th>
                </tr>
            </thead>
            <tbody id="admissionsBody">
            </tbody>
        </table>
    </div>
    
    <script>
        async function loadAdmissions() {
            const loading = document.getElementById('loading');
            const table = document.getElementById('admissionsTable');
            const message = document.getElementById('message');
            const statusFilter = document.getElementById('statusFilter').value;
            const programFilter = document.getElementById('programFilter').value;
            
            loading.style.display = 'block';
            table.style.display = 'none';
            message.innerHTML = '';
            
            try {
                let url = '../api/get_all_admissions.php';
                const params = [];
                
                if (statusFilter) params.push(`status=${encodeURIComponent(statusFilter)}`);
                if (programFilter) params.push(`program=${encodeURIComponent(programFilter)}`);
                
                if (params.length > 0) {
                    url += '?' + params.join('&');
                }
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success && data.data) {
                    renderTable(data.data);
                    updateStats(data.data);
                    table.style.display = 'table';
                } else {
                    message.innerHTML = '<div class="error">No admissions found</div>';
                }
            } catch (error) {
                message.innerHTML = '<div class="error">Error loading admissions: ' + error.message + '</div>';
            } finally {
                loading.style.display = 'none';
            }
        }
        
        function renderTable(admissions) {
            const tbody = document.getElementById('admissionsBody');
            tbody.innerHTML = '';
            
            admissions.forEach(admission => {
                const row = document.createElement('tr');
                
                const statusClass = 'status-' + (admission.status || 'pending');
                const emailStatus = admission.email_sent_date 
                    ? '<span class="email-sent">✓ Sent</span>' 
                    : '<span class="email-not-sent">✗ Not Sent</span>';
                
                const submissionDate = admission.submission_date 
                    ? new Date(admission.submission_date).toLocaleDateString('en-US')
                    : 'N/A';
                
                row.innerHTML = `
                    <td><strong>${admission.admission_id}</strong></td>
                    <td>${admission.full_name || (admission.given_name + ' ' + admission.last_name)}</td>
                    <td>${admission.email}</td>
                    <td>${admission.program}</td>
                    <td><span class="status-badge ${statusClass}">${admission.status || 'pending'}</span></td>
                    <td>${submissionDate}</td>
                    <td>${emailStatus}</td>
                `;
                
                tbody.appendChild(row);
            });
        }
        
        function updateStats(admissions) {
            const total = admissions.length;
            const pending = admissions.filter(a => (a.status || 'pending') === 'pending').length;
            const admitted = admissions.filter(a => a.status === 'admitted').length;
            const rejected = admissions.filter(a => a.status === 'rejected').length;
            
            document.getElementById('totalCount').textContent = total;
            document.getElementById('pendingCount').textContent = pending;
            document.getElementById('admittedCount').textContent = admitted;
            document.getElementById('rejectedCount').textContent = rejected;
        }
        
        // Load on page load
        loadAdmissions();
    </script>
</body>
</html>
