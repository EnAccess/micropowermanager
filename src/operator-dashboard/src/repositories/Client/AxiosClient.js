import axios from "axios"

import { config } from "@/config.js"
import { OperatorCredentials } from "@/services/OperatorCredentials.js"
import { EventBus } from "@/shared/eventbus.js"

const axiosClient = axios.create({
  baseURL: config.mpmBackendUrl,
  timeout: 120000,
  headers: {
    // Marks the call as an XHR for Laravel, and keeps any intermediary from
    // treating a 401 as a browser navigation.
    "X-Requested-With": "XMLHttpRequest",
  },
})

axiosClient.interceptors.request.use((requestConfig) => {
  const header = OperatorCredentials.header()
  if (header) {
    requestConfig.headers.Authorization = header
  }

  return requestConfig
})

axiosClient.interceptors.response.use(
  (response) => response,
  (error) => {
    // Basic auth has nothing to refresh: a 401 means the credentials are wrong or
    // no longer accepted, so drop them and let the app ask again.
    if (error.response && error.response.status === 401) {
      OperatorCredentials.clear()
      EventBus.$emit("operator.unauthenticated")
    }

    return Promise.reject(error)
  },
)

export default axiosClient
