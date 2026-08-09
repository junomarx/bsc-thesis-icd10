// REQ-INT-03: question order is randomized per playthrough; membership is
// never changed by this - it only permutes the ids already supplied.
export function shuffledOrder(questionIds) {
  const order = [...questionIds]
  for (let i = order.length - 1; i > 0; i -= 1) {
    const j = Math.floor(Math.random() * (i + 1))
    ;[order[i], order[j]] = [order[j], order[i]]
  }
  return order
}

export function summarizeResults(results, questionIds) {
  const counts = { correct: 0, suboptimal: 0, incorrect: 0 }
  for (const id of questionIds) {
    const classification = results[id]?.classification
    if (classification && classification in counts) {
      counts[classification] += 1
    }
  }
  return counts
}
