export function computeRateAmount(index, rateCount, cost) {
  const count = parseInt(rateCount)
  if (!count || count < 1) return 0
  const base = Math.floor(cost / count)
  if (index === count) {
    return cost - (count - 1) * base
  }
  return base
}
