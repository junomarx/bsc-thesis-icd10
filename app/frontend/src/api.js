const JSON_HEADERS = { 'Content-Type': 'application/json' }

async function request(path, options) {
  const response = await fetch(path, options)
  const body = await response.json().catch(() => null)
  return { status: response.status, body }
}

export function listPatients() {
  return request('/api/patients')
}

export function getPatient(patientId) {
  return request(`/api/patients/${encodeURIComponent(patientId)}`)
}

export function getQuestion(questionId) {
  return request(`/api/questions/${encodeURIComponent(questionId)}`)
}

// response: { type: 'code', code: '...' } or { type: 'none_of_above' } - the
// APIBASE-0.1 tagged-response contract, not { option_id }.
export function evaluate(questionId, response) {
  return request(`/api/questions/${encodeURIComponent(questionId)}/evaluate`, {
    method: 'POST',
    headers: JSON_HEADERS,
    body: JSON.stringify({ response }),
  })
}
