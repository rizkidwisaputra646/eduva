import './bootstrap';
import { monitorExam } from './exam/anti-cheat-monitor';
window.monitorExam = monitorExam;

import Alpine from 'alpinejs';
import { examDashboard } from './exam/dashboard';
Alpine.data('examDashboard', examDashboard);

window.Alpine = Alpine;

Alpine.start();
