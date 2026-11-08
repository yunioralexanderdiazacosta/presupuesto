// Utilidades de tema para gráficos. El tema lo cambia AppLayout.vue y avisa con 'app-theme-change'.
export const isDarkTheme = () => document.documentElement.getAttribute('data-bs-theme') === 'dark';

export const onThemeChange = (callback) => {
    window.addEventListener('app-theme-change', callback);
    return () => window.removeEventListener('app-theme-change', callback);
};

export const echartsColors = () => (isDarkTheme()
    ? {
        text: '#cccccc',
        axisLabel: '#9d9d9d',
        axisLine: '#454545',
        splitLine: '#3c3c3c',
        valueLabel: '#cccccc',
        gaugeTrack: '#3c3c3c',
        tooltipBg: '#2d2d30',
        tooltipBorder: '#454545',
        tooltipText: '#e7e7e7',
    }
    : {
        text: '#464646',
        axisLabel: '#6c757d',
        axisLine: '#e3e6ed',
        splitLine: '#f1f3f4',
        valueLabel: '#495057',
        gaugeTrack: '#e3e1e1',
        tooltipBg: '#212529',
        tooltipBorder: '#212529',
        tooltipText: '#fff',
    });
