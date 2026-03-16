import React from 'react';

function StatCard({ title, value }) {
    return (
        <div className="stat-card">
            <div className="stat-title">{title}</div>
            <div className="stat-value">{value}</div>
        </div>
    );
}

function RecentTestsTable({ tests }) {
    return (
        <table className="recent-tests-table">
            <thead>
                <tr>
                    <th>Test Title</th>
                    <th>Program</th>
                    <th>Subject</th>
                    <th>Topic</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                {tests.length === 0 && (
                    <tr>
                        <td colSpan="6">No recent tests</td>
                    </tr>
                )}
                {tests.map(t => (
                    <tr key={t.id}>
                        <td>{t.title}</td>
                        <td>{t.program}</td>
                        <td>{t.subject}</td>
                        <td>{t.topic}</td>
                        <td>
                            <span className={`status-pill ${t.status === 'Published' || t.status === 'published' ? 'published' : 'draft'}`}>
                                {t.status}
                            </span>
                        </td>
                        <td>
                            <button className="btn-view">View</button>
                            <button className="btn-edit">Edit</button>
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

export default function TeacherDashboard({ initialData = {} }) {
    const {
        programsCount = 0,
        subjectsCount = 0,
        topicsCount = 0,
        testsCount = 0,
        activeCount = 0,
        recentTests = [],
        recentActivity = [],
    } = initialData;

    return (
        <div className="dashboard-wrapper">
            <div className="dashboard-main">
                <div className="stats-row">
                    <StatCard title="Total Programs" value={programsCount} />
                    <StatCard title="Total Subjects" value={subjectsCount} />
                    <StatCard title="Total Topics" value={topicsCount} />
                    <StatCard title="Total Tests Created" value={testsCount} />
                    <StatCard title="Active" value={activeCount} />
                </div>

                <div className="quick-actions">
                    <button className="btn-primary">Create Program</button>
                    <button className="btn-secondary">Create Subject</button>
                    <button className="btn-secondary">Create Topic</button>
                    <button className="btn-secondary">Create Test</button>
                </div>

                <div className="recent-tests">
                    <h3>Recent Tests</h3>
                    <RecentTestsTable tests={recentTests} />
                </div>

                <div className="panels">
                    <div className="panel left">
                        <h4>Topics with Low Mastery</h4>
                        <p>Module 4: Algebra (Low pass rate: 36%)</p>
                        <small>Kyle R. attempted "Module 4 Quiz"</small>
                    </div>
                    <div className="panel right">
                        <h4>Frequently Missed Questions</h4>
                        <p>12 students answered "What is photosynthesis?" incorrectly</p>
                        <small>Jane S. attempted "Science MCQ"</small>
                    </div>
                </div>
            </div>

            <aside className="dashboard-side">
                <div className="activity-box">
                    <h4>Student Activity Overview</h4>
                    <p>Tests Taken Today: <strong>45</strong></p>
                    <p>Average Score: <strong>78%</strong></p>

                    <h5>Recent Activity</h5>
                    <ul className="activity-list">
                        {recentActivity.map((a, i) => (
                            <li key={i}>
                                <div className="activity-text">{a.text}</div>
                                <div className="activity-ago">{a.ago}</div>
                            </li>
                        ))}
                    </ul>
                </div>
            </aside>
        </div>
    );
}