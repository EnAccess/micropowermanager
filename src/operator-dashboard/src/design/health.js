export const HEALTH_THRESHOLD_DAYS = {
  active: 7,
  watch: 21,
}

export const HEALTH_STATUSES = ["active", "watch", "dormant"]

/**
 * Returns a semantic status key only — colours live in SCSS, so a palette change
 * never means touching JavaScript.
 */
export const healthStatus = (days, thresholds = HEALTH_THRESHOLD_DAYS) => {
  if (days === null || days === undefined) {
    return "dormant"
  }
  if (days <= thresholds.active) {
    return "active"
  }
  if (days <= thresholds.watch) {
    return "watch"
  }
  return "dormant"
}
