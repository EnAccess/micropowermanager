export const formatCount = (value) => {
  if (value === null || value === undefined) {
    return "—"
  }
  return new Intl.NumberFormat("en-US").format(Math.round(value))
}

/**
 * Currency codes are suffixed rather than run through Intl's currency style:
 * tenants bill in codes such as TZS, MMK and SLE, which Intl renders
 * inconsistently across locales.
 */
export const formatMoney = (amount, currencyCode) => {
  if (amount === null || amount === undefined) {
    return "—"
  }
  return currencyCode
    ? `${formatCount(amount)} ${currencyCode}`
    : formatCount(amount)
}

export const formatPercentage = (value, { withSign = false } = {}) => {
  if (value === null || value === undefined) {
    return "—"
  }
  const rounded = Math.round(value)
  return `${withSign && rounded > 0 ? "+" : ""}${rounded}%`
}

export const formatMonthYear = (isoDate) => {
  if (!isoDate) {
    return "—"
  }
  const date = new Date(isoDate)
  if (Number.isNaN(date.getTime())) {
    return "—"
  }
  return new Intl.DateTimeFormat("en-US", {
    month: "short",
    year: "numeric",
  }).format(date)
}

/** The API's `YYYY-MM` series labels, localised to short month names. */
export const formatPeriodLabel = (period) => {
  const date = new Date(`${period}-01T00:00:00Z`)
  if (Number.isNaN(date.getTime())) {
    return period
  }
  return new Intl.DateTimeFormat("en-US", {
    month: "short",
    timeZone: "UTC",
  }).format(date)
}

export const formatDataAsOf = (isoDate) => {
  if (!isoDate) {
    return "—"
  }
  const date = new Date(isoDate)
  if (Number.isNaN(date.getTime())) {
    return "—"
  }
  const day = new Intl.DateTimeFormat("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  }).format(date)
  const time = new Intl.DateTimeFormat("en-US", {
    hour: "2-digit",
    minute: "2-digit",
    hour12: false,
  }).format(date)
  return `${day} · ${time}`
}

/** Turns a payment-provider morph alias into something readable. */
export const formatProviderLabel = (alias) => {
  return String(alias)
    .replace(/_transaction$/, "")
    .split("_")
    .filter(Boolean)
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(" ")
}
