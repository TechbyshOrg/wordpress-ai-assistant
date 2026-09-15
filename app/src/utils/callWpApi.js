export async function callWpApi(path = '', method = 'GET', body = null, options = {}) {
  const url = `${wacdmgAdmin.apiBaseUrl}${path}`;
  const signal = options && options.signal ? options.signal : undefined;

  const res = await fetch(url, {
    method,
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': wacdmgAdmin.rest_nonce,
    },
    body: body ? JSON.stringify(body) : null,
    signal,
  });

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
