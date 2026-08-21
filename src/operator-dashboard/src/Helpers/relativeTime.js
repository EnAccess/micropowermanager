const MILLISECONDS_PER_DAY = 24 * 60 * 60 * 1000

/** Whole days elapsed, so "7 days and 22 hours ago" still counts as day 7. */
export const daysSince = (isoDate, now = new Date()) => {
  if (!isoDate) {
    return null
  }
  const then = new Date(isoDate)
  if (Number.isNaN(then.getTime())) {
    return null
  }
  return Math.max(
    0,
    Math.floor((now.getTime() - then.getTime()) / MILLISECONDS_PER_DAY),
  )
}

/**
 * Returns a translation key plus its count rather than a formatted string, so the
 * copy stays translatable and this stays unit-testable without a Vue instance.
 */
export const relativeDaysKey = (days) => {
  if (days === null || days === undefined) {
    return { key: "phrases.never", count: 0 }
  }
  if (days <= 0) {
    return { key: "phrases.today", count: 0 }
  }
  if (days === 1) {
    return { key: "phrases.yesterday", count: 1 }
  }
  if (days < 14) {
    return { key: "phrases.daysAgo", count: days }
  }
  return { key: "phrases.weeksAgo", count: Math.round(days / 7) }
}
