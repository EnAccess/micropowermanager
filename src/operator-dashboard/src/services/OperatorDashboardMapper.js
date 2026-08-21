/**
 * Maps the API's snake_case payload onto the camelCase view models the components
 * read. Keeping it explicit makes this the single seam that absorbs backend field
 * renames.
 */
export const mapTenantRow = (payload) => ({
  id: payload.id,
  name: payload.name,
  country: payload.country,
  countryCode: payload.country_code,
  usageType: payload.usage_type,
  registeredAt: payload.registered_at,
  lastActiveAt: payload.last_active_at,
  health: payload.health,
  customers: payload.customers,
  devices: {
    total: payload.devices.total,
    meters: payload.devices.meters,
    shs: payload.devices.shs,
    ebikes: payload.devices.ebikes,
  },
  transactionsThisMonth: payload.transactions_this_month,
})

export const mapTenantDetail = (payload) => ({
  ...mapTenantRow(payload),
  email: payload.email,
  phone: payload.phone,
  plugins: payload.plugins,
  monthly: {
    periods: payload.monthly.periods,
    transactions: payload.monthly.transactions,
  },
  metersAssignedToCustomer: payload.meters_assigned_to_customer,
  metersReportingLastSevenDays: payload.meters_reporting_last_seven_days,
  volumeThisMonth: payload.volume_this_month,
  currency: payload.currency,
  activity: payload.activity,
})

export const mapPlatform = (payload) => ({
  summary: {
    tenantsTotal: payload.summary.tenants_total,
    tenantsNewThisMonth: payload.summary.tenants_new_this_month,
    tenantsActive: payload.summary.tenants_active,
    tenantsActivePercentage: payload.summary.tenants_active_percentage,
    transactionsThisMonth: payload.summary.transactions_this_month,
    transactionsLastMonth: payload.summary.transactions_last_month,
    transactionsTrendPercentage: payload.summary.transactions_trend_percentage,
    customersTotal: payload.summary.customers_total,
    devicesTotal: {
      total: payload.summary.devices_total.total,
      meters: payload.summary.devices_total.meters,
      shs: payload.summary.devices_total.shs,
      ebikes: payload.summary.devices_total.ebikes,
    },
  },
  monthly: {
    periods: payload.monthly.periods,
    transactions: payload.monthly.transactions,
    byProvider: payload.monthly.by_provider,
  },
  tenants: payload.tenants.map(mapTenantRow),
  generatedAt: payload.generated_at,
  refreshing: payload.refreshing,
  stale: payload.stale,
})
