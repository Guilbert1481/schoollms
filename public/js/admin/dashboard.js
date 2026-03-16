/* =========================
   SUBJECT MODAL
========================= */
function openSubjectModal() {
    document.getElementById('subject-overlay').style.display = 'block';
    document.getElementById('subject-modal').style.display = 'block';
}

function closeSubjectModal() {
    document.getElementById('subject-overlay').style.display = 'none';
    document.getElementById('subject-modal').style.display = 'none';
}


/* =========================
   TOPIC MODAL
========================= */
function openTopicModal() {
    document.getElementById('topic-overlay').style.display = 'block';
    document.getElementById('topic-modal').style.display = 'block';
}

function closeTopicModal() {
    document.getElementById('topic-overlay').style.display = 'none';
    document.getElementById('topic-modal').style.display = 'none';
}


/* =========================
   PROGRAM MODAL
========================= */
function openProgramModal() {
    document.getElementById('program-overlay').style.display = 'block';
    document.getElementById('program-modal').style.display = 'block';
}

function closeProgramModal() {
    document.getElementById('program-overlay').style.display = 'none';
    document.getElementById('program-modal').style.display = 'none';
}


/* =========================
   GLOBAL ESC KEY CLOSE
========================= */
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeSubjectModal();
        closeTopicModal();
        closeProgramModal();
    }
});





        lucide.createIcons();

        // 1. Academic Trend (Decision: Is learning happening?)
        new Chart(document.getElementById('academicTrend'), {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 4', 'Week 8', 'Week 12', 'Mid-Term'],
                datasets: [{
                    label: 'Actual GPA',
                    data: [78, 80, 79, 83, 84.5],
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 5,
                    pointRadius: 6,
                    pointBackgroundColor: '#fff',
                    pointBorderWidth: 3
                }, {
                    label: 'Target',
                    data: [80, 80, 80, 80, 80],
                    borderColor: '#e2e8f0',
                    borderDash: [5, 5],
                    fill: false,
                    pointRadius: 0
                }]
            },
            options: { 
                responsive: true, maintainAspectRatio: false, 
                plugins: { legend: { display: false } },
                scales: { 
                    y: { grid: { display: false }, ticks: { font: { weight: 'bold' } } }, 
                    x: { grid: { display: false }, ticks: { font: { weight: 'bold' } } } 
                }
            }
        });

        // 2. Pass vs Fail (Decision: Where is the failure bottleneck?)
        new Chart(document.getElementById('passFailChart'), {
            type: 'bar',
            data: {
                labels: ['G7', 'G8', 'G9', 'G10', 'G11', 'G12'],
                datasets: [
                    { label: 'Pass', data: [92, 88, 76, 95, 82, 98], backgroundColor: '#4f46e5', borderRadius: 8 },
                    { label: 'Fail', data: [8, 12, 24, 5, 18, 2], backgroundColor: '#f43f5e', borderRadius: 8 }
                ]
            },
            options: { 
                responsive: true, maintainAspectRatio: false, 
                plugins: { legend: { display: false } },
                scales: { 
                    x: { stacked: true, grid: { display: false } }, 
                    y: { stacked: true, grid: { display: false } } 
                }
            }
        });

        // 3. Risk Doughnut (Decision: How many dropouts are coming?)
        new Chart(document.getElementById('riskDoughnut'), {
            type: 'doughnut',
            data: {
                labels: ['Stable', 'At-Risk', 'Critical'],
                datasets: [{
                    data: [84, 12, 4],
                    backgroundColor: ['#10b981', '#f59e0b', '#f43f5e'],
                    borderWidth: 0
                }]
            },
            options: { 
                responsive: true, maintainAspectRatio: false, cutout: '80%', 
                plugins: { legend: { display: false } } 
            }
        });

        // 4. Fee Collection (Decision: Do we have cash for next month?)
        new Chart(document.getElementById('financeChart'), {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr'],
                datasets: [{
                    label: 'Collected',
                    data: [240, 210, 180, 220],
                    backgroundColor: '#10b981',
                    borderRadius: 8
                }, {
                    label: 'Target',
                    data: [250, 250, 250, 250],
                    backgroundColor: '#f1f5f9',
                    borderRadius: 8
                }]
            },
            options: { 
                responsive: true, maintainAspectRatio: false, 
                plugins: { legend: { display: false } },
                scales: { 
                    x: { grid: { display: false } }, 
                    y: { grid: { display: false } } 
                }
            }
        });
    


