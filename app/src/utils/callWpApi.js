export async function callWpApi(path = '', method = 'GET', body = null) {
  const url = `${wacdmgAdmin.apiBaseUrl}${path}`;

  const res = await fetch(url, {
    method,
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': wacdmgAdmin.rest_nonce,
    },
    body: body ? JSON.stringify(body) : null,
  });

  if (!res.ok) {
    const error = await res.json();
    throw new Error(error.message || 'API error');
  }

  return await res.json();
}


// import { API_BASE_URL } from './config';

// export async function callWpApi(path = '', method = 'GET', body = null) {
//   const url = `${API_BASE_URL}${path}`;

//   // Automatically include nonce from the global localized script
//   const nonce = typeof wacdmgAdmin !== 'undefined' ? wacdmgAdmin.nonce : '';

//   const res = await fetch(url, {
//     method,
//     headers: {
//       'Content-Type': 'application/json',
//     },
//     body: JSON.stringify({
//       nonce,
//       ...(body || {}),
      
//     }),
//   });

//   if (!res.ok) throw new Error('API error');
//   return await res.json();
// }
