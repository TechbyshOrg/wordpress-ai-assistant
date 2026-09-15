export async function callWpApi(path = '', method = 'GET', body = null, options = {}) {
  const url = `${wacdmgAdmin.apiBaseUrl}${path}`;
  const signal = options && options.signal ? options.signal : undefined;
  const headers = {
    'X-WP-Nonce': wacdmgAdmin.rest_nonce,
  };

  const fetchOptions = {
    method,
    headers,
    credentials: 'same-origin',
    cache: 'no-store',
  };

  if (signal) {
    fetchOptions.signal = signal;
  }

  if (body !== null && body !== undefined && method !== 'GET') {
    headers['Content-Type'] = 'application/json';
    fetchOptions.body = JSON.stringify(body);
  }

  const res = await fetch(url, fetchOptions);

  let data = null;
  try {
    data = await res.json();
  } catch (e) {
    data = null;
  }

  if (!res.ok) {
    const msg =
      (data && data.data && data.data.message) ||
      (data && data.message) ||
      `API error (${res.status})`;
    const err = new Error(msg);
    err.status = res.status;
    err.data = data;
    throw err;
  }

  return data;
}
