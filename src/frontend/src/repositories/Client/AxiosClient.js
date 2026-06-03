import axios from "axios"

import { config } from "@/config.js"
import { EventBus } from "@/shared/eventbus.js"

export const baseUrl = config.mpmBackendUrl
export const baseUrlExternal = config.mpmBackendUrlExternal

// The Vuex store is wired in from main.js after construction. Importing it
// here directly would create a load-order cycle: store -> auth module ->
// AuthenticationRepository -> this file.
let storeRef = null
export function attachAuthStore(store) {
  storeRef = store
}

const REFRESH_URL = "/api/auth/refresh"
let pendingRefresh = null

function loadCustomHeadersFromEnv() {
  const raw = process.env.VUE_APP_CUSTOM_HEADERS

  if (!raw) return {}

  try {
    const parsed = JSON.parse(raw)

    if (typeof parsed !== "object" || Array.isArray(parsed)) {
      throw new Error("CUSTOM_HEADERS_JSON must be an object")
    }
    return parsed
  } catch (err) {
    console.error("Failed to parse CUSTOM_HEADERS_JSON:", err.message)
    return {}
  }
}

const axiosClient = axios.create({
  baseURL: config.mpmBackendUrl,
  timeout: 120000,
})

axiosClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem("token")
    if (token) {
      config.headers["Authorization"] = "Bearer " + token
    }

    const customHeaders = loadCustomHeadersFromEnv()
    if (customHeaders) {
      config.headers = {
        ...config.headers,
        ...customHeaders,
      }
    }

    return config
  },
  (error) => {
    Promise.reject(error)
  },
)

axiosClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config
    const status = error.response && error.response.status

    if (status !== 401 || !originalRequest) {
      return Promise.reject(error)
    }
    if (!storeRef) {
      console.error(
        "AxiosClient: auth store not attached; cannot refresh expired token. " +
          "attachAuthStore(store) must run during app bootstrap (see main.js).",
      )
      return Promise.reject(error)
    }
    if (originalRequest.url && originalRequest.url.includes(REFRESH_URL)) {
      return Promise.reject(error)
    }
    if (originalRequest._retry) {
      return Promise.reject(error)
    }
    originalRequest._retry = true

    try {
      if (!pendingRefresh) {
        pendingRefresh = storeRef.dispatch("auth/refreshToken").finally(() => {
          pendingRefresh = null
        })
      }
      await pendingRefresh

      const newToken = localStorage.getItem("token")
      if (newToken) {
        originalRequest.headers["Authorization"] = "Bearer " + newToken
      }
      return axiosClient(originalRequest)
    } catch (refreshError) {
      EventBus.$emit("session.end", true)
      return Promise.reject(refreshError)
    }
  },
)

export default axiosClient
