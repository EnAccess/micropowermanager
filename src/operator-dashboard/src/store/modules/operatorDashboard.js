import { OperatorDashboardService } from "@/services/OperatorDashboardService.js"

const POLL_INTERVAL_MS = 5000
const POLL_MAX_ATTEMPTS = 24

const sleep = (milliseconds) =>
  new Promise((resolve) => {
    setTimeout(resolve, milliseconds)
  })

export const namespaced = true

export const state = {
  operatorDashboardService: new OperatorDashboardService(),
  summary: null,
  monthly: { periods: [], transactions: [], byProvider: {} },
  tenants: [],
  tenantDetail: null,
  generatedAt: null,
  stale: false,
  refreshing: false,
  loading: { platform: false, detail: false },
}

export const mutations = {
  SET_PLATFORM(state, platform) {
    state.summary = platform.summary
    state.monthly = platform.monthly
    state.tenants = platform.tenants
    state.generatedAt = platform.generatedAt
    state.stale = platform.stale
    state.refreshing = platform.refreshing
  },
  SET_TENANT_DETAIL(state, tenantDetail) {
    state.tenantDetail = tenantDetail
  },
  SET_LOADING(state, { section, value }) {
    state.loading = { ...state.loading, [section]: value }
  },
  SET_REFRESHING(state, refreshing) {
    state.refreshing = refreshing
  },
}

export const actions = {
  async fetchPlatform({ commit, state }) {
    commit("SET_LOADING", { section: "platform", value: true })
    try {
      commit("SET_PLATFORM", await state.operatorDashboardService.platform())
    } finally {
      commit("SET_LOADING", { section: "platform", value: false })
    }
  },

  async fetchTenantDetail({ commit, state }, companyId) {
    commit("SET_LOADING", { section: "detail", value: true })
    try {
      commit(
        "SET_TENANT_DETAIL",
        await state.operatorDashboardService.tenant(companyId),
      )
    } finally {
      commit("SET_LOADING", { section: "detail", value: false })
    }
  },

  /**
   * Polls from the action rather than a component timer, so navigating away
   * mid-rebuild cannot orphan the loop. It ends when the server publishes a newer
   * `generated_at`, or when the backend stops reporting a rebuild in flight.
   */
  async refresh({ commit, dispatch, state }) {
    if (state.refreshing) {
      return
    }

    const generatedAtBeforeRebuild = state.generatedAt
    commit("SET_REFRESHING", true)

    await state.operatorDashboardService.refresh()

    for (let attempt = 0; attempt < POLL_MAX_ATTEMPTS; attempt += 1) {
      await sleep(POLL_INTERVAL_MS)
      await dispatch("fetchPlatform")

      if (state.generatedAt !== generatedAtBeforeRebuild) {
        return
      }
      if (!state.refreshing) {
        throw { message: "phrases.refreshFailed", type: "refresh" }
      }
    }

    commit("SET_REFRESHING", false)
    throw { message: "phrases.refreshTimedOut", type: "refresh" }
  },
}

export const getters = {
  tenantsTotal: (state) => (state.summary ? state.summary.tenantsTotal : 0),
  // Derived on read: the attention list is never stored, so it cannot go stale
  // against the tenant rows it summarises.
  attentionTenants: (state) =>
    state.tenants
      .filter((tenant) => tenant.health !== "active")
      .sort((left, right) => {
        const leftDate = left.lastActiveAt
          ? new Date(left.lastActiveAt).getTime()
          : 0
        const rightDate = right.lastActiveAt
          ? new Date(right.lastActiveAt).getTime()
          : 0

        return leftDate - rightDate
      }),
  tenantById: (state) => (companyId) =>
    state.tenants.find((tenant) => String(tenant.id) === String(companyId)),
}
