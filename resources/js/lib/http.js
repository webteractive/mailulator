const baseURL = window.MAILULATOR_CONFIG?.apiBase ?? '/mailulator/api'

function readCookie(name) {
  const match = document.cookie.split('; ').find(c => c.startsWith(name + '='))
  return match ? decodeURIComponent(match.slice(name.length + 1)) : null
}

function csrfHeaders() {
  // Laravel refreshes XSRF-TOKEN on every response. Prefer it; the meta tag
  // is a fallback for the initial boot before any response has set the cookie.
  const xsrf = readCookie('XSRF-TOKEN')
  if (xsrf) return { 'X-XSRF-TOKEN': xsrf }

  const meta = document.querySelector('meta[name="csrf-token"]')?.content
  return meta ? { 'X-CSRF-TOKEN': meta } : {}
}

function buildUrl(path, params) {
  const url = new URL(path.startsWith('http') ? path : baseURL + path, window.location.origin)
  if (params) {
    for (const [k, v] of Object.entries(params)) {
      if (v !== undefined && v !== null && v !== '') url.searchParams.set(k, v)
    }
  }
  return url.toString()
}

async function request(method, path, { params, body, headers } = {}) {
  const hasBody = body !== undefined && body !== null
  const isForm = hasBody && typeof FormData !== 'undefined' && body instanceof FormData
  const needsCsrf = !['GET', 'HEAD', 'OPTIONS'].includes(method.toUpperCase())

  const response = await fetch(buildUrl(path, params), {
    method,
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(needsCsrf ? csrfHeaders() : {}),
      ...(hasBody && !isForm ? { 'Content-Type': 'application/json' } : {}),
      ...headers,
    },
    body: hasBody ? (isForm ? body : JSON.stringify(body)) : undefined,
  })

  const contentType = response.headers.get('content-type') ?? ''
  const data = contentType.includes('application/json') ? await response.json() : await response.text()

  if (!response.ok) {
    const error = new Error(`HTTP ${response.status}`)
    error.status = response.status
    error.data = data
    throw error
  }

  return { data, status: response.status }
}

export const http = {
  get: (path, opts) => request('GET', path, opts),
  post: (path, body, opts) => request('POST', path, { ...opts, body }),
  patch: (path, body, opts) => request('PATCH', path, { ...opts, body }),
  put: (path, body, opts) => request('PUT', path, { ...opts, body }),
  delete: (path, opts) => request('DELETE', path, opts),
}
