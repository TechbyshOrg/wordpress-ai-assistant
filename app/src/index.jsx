import React from 'react';
import ReactDOM from 'react-dom/client';
import './index.css';
import DescriptionGenerator from './components/DescriptionGenerator';
import Settings from './components/Settings';
import ImageGenerator from './components/ImageGenerator';
import ContentTemplates from './components/ContentTemplates';
import UsageLog from './components/UsageLog';

/**
 * Multi-page React mount.
 * Each admin page has its own container element ID.
 */
const mountApp = (elementId, Component) => {
    const container = document.getElementById(elementId);
    if (!container) return;
    const root = ReactDOM.createRoot(container);
    root.render(
        <React.StrictMode>
            <Component />
        </React.StrictMode>
    );
};

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
    // WooCommerce product editor — Description generator
    mountApp('wacdmg-description-container', DescriptionGenerator);

    // Admin pages
    mountApp('wacdmg-settings-container', Settings);
    mountApp('wacdmg-image-generator-container', ImageGenerator);
    mountApp('wacdmg-templates-container', ContentTemplates);
    mountApp('wacdmg-usage-log-container', UsageLog);
});
