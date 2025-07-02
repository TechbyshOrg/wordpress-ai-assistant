import React from 'react';
import ReactDOM from 'react-dom/client';
import './index.css';
import App from './App';
import AdminSettings from './AdminSettings';

const descriptionContainer = document.getElementById('wacdmg-description-container');
if (descriptionContainer) {
  const root = ReactDOM.createRoot(descriptionContainer);
  root.render(
    <React.StrictMode>
      <App />
    </React.StrictMode>
  );
}

const settingsContainer = document.getElementById('wacdmg-settings-container');
if (settingsContainer) {
  const adminRoot = ReactDOM.createRoot(settingsContainer);
  adminRoot.render(
    <React.StrictMode>
      <AdminSettings />
    </React.StrictMode>
  );
}
