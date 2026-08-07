const JSON_HEADERS = { 'Content-Type': 'application/json' }

async function request(path, options) {
  const response = await fetch(path, options)
  const body = await response.json().catch(() => null)
  return { status: response.status, body }
}

export function listCases() {
  return request('/api/cases')
}

export function getCase(caseId) {
  return request(`/api/cases/${encodeURIComponent(caseId)}`)
}

export function evaluate(caseId, submittedCode) {
  return request(`/api/cases/${encodeURIComponent(caseId)}/evaluate`, {
    method: 'POST',
    headers: JSON_HEADERS,
    body: JSON.stringify({ submitted_code: submittedCode }),
  })
}
