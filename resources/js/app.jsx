import React from 'react';
import { createRoot } from 'react-dom/client';
import TeacherDashboard from './components/TeacherDashboard';

// Optional: import global CSS here if using Vite
import '../css/dashboard.css';

document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('teacher-dashboard');
    if (!el) return;

    const initialData = window.__TEACHER_DASHBOARD__ || {};

    const root = createRoot(el);
    root.render(<TeacherDashboard initialData={initialData} />);
});