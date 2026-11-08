// Colores por defecto de Chart.js según el tema; se importa desde cada vista con gráficos.
import { Chart } from 'chart.js';
import { isDarkTheme, onThemeChange } from './themeColors';

const applyDefaults = () => {
    const dark = isDarkTheme();
    Chart.defaults.color = dark ? '#cccccc' : '#666';
    Chart.defaults.borderColor = dark ? 'rgba(255, 255, 255, 0.12)' : 'rgba(0, 0, 0, 0.1)';
};

applyDefaults();

onThemeChange(() => {
    applyDefaults();
    Object.values(Chart.instances).forEach((chart) => chart.update());
});

export const chartTextColor = () => Chart.defaults.color;
