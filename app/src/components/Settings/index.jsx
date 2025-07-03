import { useState } from 'react';
import { callWpApi } from '../../utils/callWpApi';
import './style.css';
import { chatgptModels, groqModels } from '../../utils/variables';

const Settings = () => {
  const [provider, setProvider] = useState('chatgpt');
  const [chatgptKey, setChatgptKey] = useState('');
  const [groqKey, setGroqKey] = useState('');
  const [chatgptModel, setChatgptModel] = useState(chatgptModels[0].value);
  const [groqModel, setGroqModel] = useState(groqModels[0].value);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');

  const handleProviderChange = (e) => {
    setProvider(e.target.value);
    setMessage('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);

    const formData = {
      provider,
      apiKey: provider === 'chatgpt' ? chatgptKey : groqKey,
      model: provider === 'chatgpt' ? chatgptModel : groqModel,
    };

    callWpApi('/save-settings', 'POST', { formData })
      .then((response) => {
        if (response.success) {
          setMessage('Settings saved successfully!');
        } else {
          setMessage('Failed to save settings: ' + response.data.message);
        }
        setSaving(false);
      })
      .catch(() => {
        setMessage('An error occurred while saving settings.');
        setSaving(false);
      });
  };

  return (
    <div>
      <form className="wacdmg-form" onSubmit={handleSubmit}>
        <h2 className="wacdmg-title">AI Provider Settings</h2>
        
        <div className="wacdmg-form-section">
          <h3>Select Provider</h3>
          <div className="wacdmg-provider-group">
            <label className="wacdmg-provider-label">
              <input
                type="radio"
                name="provider"
                value="chatgpt"
                checked={provider === 'chatgpt'}
                onChange={handleProviderChange}
                className="wacdmg-radio"
              />
              ChatGPT
            </label>
            <label className="wacdmg-provider-label">
              <input
                type="radio"
                name="provider"
                value="groq"
                checked={provider === 'groq'}
                onChange={handleProviderChange}
                className="wacdmg-radio"
              />
              Groq
            </label>
          </div>
        </div>

        {provider === 'chatgpt' && (
          <div className="wacdmg-form-section">
            <h3>ChatGPT Settings</h3>
            <div className="wacdmg-form-row">
              <label className="wacdmg-label">API Key</label>
              <input
                type="text"
                value={chatgptKey}
                onChange={e => setChatgptKey(e.target.value)}
                className="wacdmg-input"
                placeholder="sk-...your-api-key"
              />
            </div>
            <div className="wacdmg-form-row">
              <label className="wacdmg-label">Model</label>
              <select
                value={chatgptModel}
                onChange={e => setChatgptModel(e.target.value)}
                className="wacdmg-select"
              >
                {chatgptModels.map(model => (
                  <option key={model.value} value={model.value}>
                    {model.label === model.value ? model.label : `${model.label} - ${model.value}`}
                  </option>
                ))}
              </select>
            </div>
          </div>
        )}

        {provider === 'groq' && (
          <div className="wacdmg-form-section">
            <h3>Groq Settings</h3>
            <div className="wacdmg-form-row">
              <label className="wacdmg-label">API Key</label>
              <input
                type="text"
                value={groqKey}
                onChange={e => setGroqKey(e.target.value)}
                className="wacdmg-input"
                placeholder="gsk-...your-api-key"
              />
            </div>
            <div className="wacdmg-form-row">
              <label className="wacdmg-label">Model</label>
              <select
                value={groqModel}
                onChange={e => setGroqModel(e.target.value)}
                className="wacdmg-select"
              >
                {groqModels.map(model => (
                  <option key={model.value} value={model.value}>
                    {model.label === model.value ? model.label : `${model.label} - ${model.value}`}
                  </option>
                ))}
              </select>
            </div>
          </div>
        )}

        <div className="wacdmg-actions">
          <button className="wacdmg-button" type="submit" disabled={saving}>
            {saving ? (
              <>
                <span className="dashicons dashicons-update spin" style={{marginRight: '5px'}}></span>
                Saving...
              </>
            ) : 'Save Settings'}
          </button>
          {message && (
            <div className={`wacdmg-message ${message.includes('success') ? 'success' : 'error'}`}>
              {message}
            </div>
          )}
        </div>
      </form>
      <div className="wacdmg-docs">
        {provider === 'chatgpt' && (
          <p>
            For ChatGPT API Key, visit{' '}
            <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer">
              OpenAI API Keys
            </a>.
          </p>
        )}
        {provider === 'groq' && (
          <p>
            For Groq API Key, visit{' '}
            <a href="https://console.groq.com/keys" target="_blank" rel="noopener noreferrer">
              Groq Console API Keys
            </a>.
          </p>
        )}
      </div>
    </div>
  );
};

export default Settings;