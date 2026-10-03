import 'vuetify/styles';
import '@mdi/font/css/materialdesignicons.css';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';

const savedTheme = localStorage.getItem('app_theme') || 'dark';

const customDarkTheme = {
  dark: true,
  colors: {
    background: '#0F172A',
    surface: '#1E293B',
    primary: '#3B82F6',
    'primary-darken-1': '#1D4ED8',
    secondary: '#06B6D4',
    accent: '#8B5CF6',
    error: '#EF4444',
    info: '#3B82F6',
    success: '#10B981',
    warning: '#F59E0B',
  },
};

const customLightTheme = {
  dark: false,
  colors: {
    background: '#F8FAFC',
    surface: '#FFFFFF',
    primary: '#2563EB',
    'primary-darken-1': '#1D4ED8',
    secondary: '#0891B2',
    accent: '#7C3AED',
    error: '#DC2626',
    info: '#2563EB',
    success: '#059669',
    warning: '#D97706',
  },
};

const vuetify = createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: savedTheme,
    themes: {
      dark: customDarkTheme,
      light: customLightTheme,
    },
  },
});

export default vuetify;
